<?php

namespace App\Http\Controllers;

use App\Models\FileDokumen;
use App\Models\FileVersion;
use App\Models\Workspace;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * UC-05 / TMT-005: repository berkas draf dengan file versioning.
 *
 * - Berkas disimpan pada disk privat (storage/app/private) sehingga hanya
 *   bisa diunduh/pratinjau oleh anggota workspace melalui controller.
 * - Unggahan dengan nama sama otomatis menjadi versi baru (v1, v2, ...).
 */
class RepositoryController extends Controller
{
    public function index(Request $request, Workspace $workspace)
    {
        $role = user_role_in($workspace->id_workspace);
        $files = $workspace->files()
            ->with([
                'task:id_task,judul',
                'pembuat:id_user,nama',
                'versions.uploader:id_user,nama',
            ])
            ->orderByDesc('created_at')
            ->get();

        $totalUkuran = $files->sum(fn ($f) => $f->versions->sum('ukuran_byte'));
        $totalVersi = $files->sum(fn ($f) => $f->versions->count());

        $tugas = $workspace->tasks()->orderBy('judul')->get(['id_task', 'judul']);

        return view('repository.index', compact('workspace', 'files', 'totalUkuran', 'totalVersi', 'tugas'));
    }

    /** FR-016 s.d. FR-018: unggah berkas + validasi + versioning */
    public function upload(Request $request, Workspace $workspace)
    {
        $maksKb = (int) config('taskmate.max_file_kb', 20480);

        $data = $request->validate([
            'berkas' => [
                'required',
                'file',
                'max:'.$maksKb,
                'mimes:'.config('taskmate.allowed_file_mimes'),
            ],
            'id_task' => ['nullable', 'integer'],
            'catatan_revisi' => ['nullable', 'string', 'max:255'],
        ], [
            'berkas.required' => 'Pilih berkas yang akan diunggah.',
            'berkas.max' => 'Ukuran berkas melebihi batas maksimal '.round($maksKb / 1024).' MB.',
            'berkas.mimes' => 'Format berkas tidak didukung. Gunakan: PDF, DOC/DOCX, PPT/PPTX, XLS/XLSX, TXT, ZIP, PNG, atau JPG.',
            'berkas.uploaded' => 'Unggahan gagal. Periksa ukuran berkas dan pengaturan server (php.ini).',
        ]);

        $idTask = null;
        if ($request->filled('id_task')) {
            $tugas = $workspace->tasks()->find((int) $request->input('id_task'));
            $idTask = $tugas?->id_task;
        }

        $unggahan = $request->file('berkas');
        $ext = strtolower($unggahan->getClientOriginalExtension() ?: $unggahan->extension());
        $base = pathinfo($unggahan->getClientOriginalName(), PATHINFO_FILENAME);
        $base = trim(preg_replace('/[^\p{L}\p{N}\-_ ]+/u', '_', $base));
        if ($base === '' || $base === '_') {
            $base = 'berkas';
        }
        $base = Str::limit($base, 120, '');
        $namaFile = $base.($ext !== '' ? '.'.$ext : '');

        $dokumen = FileDokumen::firstOrCreate(
            ['id_workspace' => $workspace->id_workspace, 'nama_file' => $namaFile],
            ['dibuat_oleh' => $request->user()->id_user, 'id_task' => $idTask]
        );

        if ($idTask) {
            $dokumen->update(['id_task' => $idTask]);
        }

        $nomor = ((int) $dokumen->versions()->max('nomor_versi')) + 1;

        $path = $unggahan->storeAs(
            'files/workspace_'.$workspace->id_workspace.'/file_'.$dokumen->id_file,
            'v'.$nomor.'_'.$namaFile,
            'local'
        );

        FileVersion::create([
            'id_file' => $dokumen->id_file,
            'nomor_versi' => $nomor,
            'path_file' => $path,
            'ukuran_byte' => (int) $unggahan->getSize(),
            'catatan_revisi' => $data['catatan_revisi'] ?? null,
            'id_uploader' => $request->user()->id_user,
            'waktu_unggah' => now(),
        ]);

        return back()->with('sukses', 'Berkas "'.$namaFile.'" tersimpan sebagai versi v'.$nomor.'.');
    }

    /** FR-019: mengunduh versi tertentu (hanya anggota workspace) */
    public function download(Request $request, FileVersion $version)
    {
        $file = $version->file()->with('workspace')->firstOrFail();
        $workspace = $file->workspace;

        abort_unless($workspace && $workspace->hasMember($request->user()->id_user), 403,
            'Anda bukan anggota workspace tempat berkas ini berada.');

        $disk = Storage::disk('local');
        abort_unless($disk->exists($version->path_file), 404, 'Berkas tidak ditemukan pada penyimpanan server.');

        $base = pathinfo($file->nama_file, PATHINFO_FILENAME);
        $ext = pathinfo($file->nama_file, PATHINFO_EXTENSION);
        $namaUnduh = $version->nomor_versi > 1
            ? $base.'_v'.$version->nomor_versi.($ext ? '.'.$ext : '')
            : $file->nama_file;

        return response()->download($disk->path($version->path_file), $namaUnduh);
    }

    /** Pratinjau PDF & gambar langsung di aplikasi (SRS 3.3 built-in viewer) */
    public function preview(Request $request, FileVersion $version)
    {
        $file = $version->file()->with('workspace')->firstOrFail();
        $workspace = $file->workspace;

        abort_unless($workspace && $workspace->hasMember($request->user()->id_user), 403,
            'Anda bukan anggota workspace tempat berkas ini berada.');

        $disk = Storage::disk('local');
        abort_unless($disk->exists($version->path_file), 404, 'Berkas tidak ditemukan pada penyimpanan server.');

        $ext = $file->ekstensi();
        abort_unless(in_array($ext, ['pdf', 'png', 'jpg', 'jpeg']), 404, 'Format berkas ini tidak mendukung pratinjau.');

        $mime = match ($ext) {
            'pdf' => 'application/pdf',
            'png' => 'image/png',
            default => 'image/jpeg',
        };

        return response()->file($disk->path($version->path_file), [
            'Content-Type' => $mime,
            'Content-Disposition' => 'inline; filename="'.addcslashes($file->nama_file, '"\r\n').'"',
        ]);
    }

    /** FR-020: menghapus berkas beserta seluruh versinya (Project Leader) */
    public function destroy(Request $request, Workspace $workspace, FileDokumen $file)
    {
        abort_unless(is_leader_in($workspace->id_workspace), 403, 'Hanya Project Leader yang dapat menghapus berkas.');

        if ($file->id_workspace !== $workspace->id_workspace) {
            abort(404, 'Berkas tidak ditemukan pada workspace ini.');
        }

        Storage::disk('local')->deleteDirectory('files/workspace_'.$workspace->id_workspace.'/file_'.$file->id_file);

        $nama = $file->nama_file;
        $file->delete(); // versi ikut terhapus (FK cascade)

        return back()->with('sukses', 'Berkas "'.$nama.'" beserta seluruh versinya telah dihapus.');
    }
}
