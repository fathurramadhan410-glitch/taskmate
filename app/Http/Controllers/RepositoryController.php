<?php

namespace App\Http\Controllers;

use App\Models\FileDokumen;
use App\Models\FileVersion;
use App\Models\Task;
use App\Models\Workspace;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * UC-05 / TMT-005: repository berkas dengan file versioning.
 *
 * Isi repository terbagi dua kategori:
 *  - Draf Tugas         : diunggah anggota (Member hanya untuk tugas miliknya) atau leader.
 *  - Materi & Referensi : HANYA diunggah Project Leader, dapat dibaca semua anggota.
 *
 * - Berkas disimpan pada disk privat (storage/app/private) sehingga hanya
 *   bisa diunduh/pratinjau oleh anggota workspace melalui controller.
 * - Unggahan dengan nama sama (pada kategori yang sama) otomatis menjadi
 *   versi baru (v1, v2, ...).
 */
class RepositoryController extends Controller
{
    public function index(Request $request, Workspace $workspace)
    {
        $role = user_role_in($workspace->id_workspace);
        $userId = $request->user()->id_user;
        $isLeader = $role === 'leader';

        $semua = $workspace->files()
            ->with([
                'task:id_task,judul',
                'pembuat:id_user,nama',
                'versions.uploader:id_user,nama',
            ])
            ->orderByDesc('created_at')
            ->get();

        $drafs = $semua->where('kategori', FileDokumen::KATEGORI_DRAF)->values();
        $materi = $semua->where('kategori', FileDokumen::KATEGORI_MATERI)->values();

        $totalUkuran = $semua->sum(fn ($f) => $f->versions->sum('ukuran_byte'));
        $totalVersi = $semua->sum(fn ($f) => $f->versions->count());

        // Pilihan "Tugas Terkait" saat mengunggah draf:
        //  - Leader : semua tugas workspace.
        //  - Member : hanya tugas yang menunjuk dirinya sebagai penanggung jawab.
        $tugasQuery = $workspace->tasks()->with('penanggungJawab:id_user,nama')->orderBy('judul');
        if (! $isLeader) {
            $tugasQuery->where('id_penanggung_jawab', $userId);
        }
        $tugasDraf = $tugasQuery->get(['id_task', 'judul', 'id_penanggung_jawab']);

        // Materi boleh dikaitkan ke tugas mana pun (hanya dipakai leader)
        $tugasMateri = $isLeader ? $tugasDraf : collect();

        // Tautan "Unggah draf" dari detail tugas: pilih tugasnya & buka formulir
        $tugasDipilih = (int) $request->query('tugas');
        if (! $tugasDraf->contains('id_task', $tugasDipilih)) {
            $tugasDipilih = null;
        }

        $tab = $request->query('tab') === 'materi' ? 'materi' : 'draf';

        return view('repository.index', compact(
            'workspace', 'role', 'drafs', 'materi', 'totalUkuran', 'totalVersi',
            'tugasDraf', 'tugasMateri', 'tugasDipilih', 'tab'
        ));
    }

    /** FR-016 s.d. FR-018: unggah draf tugas + validasi + versioning */
    public function upload(Request $request, Workspace $workspace)
    {
        $role = user_role_in($workspace->id_workspace);
        $user = $request->user();

        $data = $request->validate($this->aturanBerkas(), $this->pesanBerkas());

        $idTask = null;
        if ($request->filled('id_task')) {
            $tugasQuery = $workspace->tasks()->whereKey((int) $request->input('id_task'));
            if ($role !== 'leader') {
                // Member hanya boleh mengaitkan draf dengan tugas miliknya sendiri
                $tugasQuery->where('id_penanggung_jawab', $user->id_user);
            }
            $tugas = $tugasQuery->first();

            if (! $tugas) {
                return back()->withInput()->withErrors([
                    'id_task' => $role === 'leader'
                        ? 'Tugas tidak ditemukan pada workspace ini.'
                        : 'Anda hanya dapat mengaitkan draf dengan tugas yang ditugaskan kepada Anda.',
                ]);
            }
            $idTask = $tugas->id_task;
        }

        return $this->simpan(
            $request, $workspace, FileDokumen::KATEGORI_DRAF, $idTask,
            $data['catatan_revisi'] ?? null, $role === 'leader'
        );
    }

    /** Materi & referensi: hanya Project Leader */
    public function uploadMateri(Request $request, Workspace $workspace)
    {
        abort_unless(is_leader_in($workspace->id_workspace), 403,
            'Hanya Project Leader yang dapat mengunggah materi / referensi.');

        $data = $request->validateWithBag('materi', $this->aturanBerkas(), $this->pesanBerkas());

        $idTask = null;
        if ($request->filled('id_task')) {
            $tugas = $workspace->tasks()->whereKey((int) $request->input('id_task'))->first();

            if (! $tugas) {
                return back()->withInput()->withErrors(
                    ['id_task' => 'Tugas tidak ditemukan pada workspace ini.'], 'materi'
                );
            }
            $idTask = $tugas->id_task;
        }

        return $this->simpan(
            $request, $workspace, FileDokumen::KATEGORI_MATERI, $idTask,
            $data['catatan_revisi'] ?? null, true
        );
    }

    /** FR-019: mengunduh versi tertentu (hanya anggota workspace) */
    public function download(Request $request, FileVersion $version)
    {
        [$file, $disk] = $this->bukaVersi($request, $version);

        $base = pathinfo($file->nama_file, PATHINFO_FILENAME);
        $ext = pathinfo($file->nama_file, PATHINFO_EXTENSION);
        $namaUnduh = $version->nomor_versi > 1
            ? $base.'_v'.$version->nomor_versi.($ext ? '.'.$ext : '')
            : $file->nama_file;

        return response()->download($disk->path($version->path_file), $namaUnduh);
    }

    /** Pratinjau PDF & gambar langsung di aplikasi (ditampilkan pada modal, tanpa tab baru) */
    public function preview(Request $request, FileVersion $version)
    {
        [$file, $disk] = $this->bukaVersi($request, $version);

        $ext = $file->ekstensi();
        abort_unless(preview_type($ext) !== null, 404, 'Format berkas ini tidak mendukung pratinjau.');

        $mime = match ($ext) {
            'pdf' => 'application/pdf',
            'png' => 'image/png',
            default => 'image/jpeg',
        };

        return response()->file($disk->path($version->path_file), [
            'Content-Type' => $mime,
            'Content-Disposition' => 'inline; filename="'.addcslashes($file->nama_file, "\"\r\n").'"',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, max-age=0, must-revalidate',
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
        $kategori = $file->kategori;
        $file->delete(); // versi ikut terhapus (FK cascade)

        return redirect()
            ->route('repository.index', [$workspace, 'tab' => $kategori])
            ->with('sukses', 'Berkas "'.$nama.'" beserta seluruh versinya telah dihapus.');
    }

    /* ============================ INTERNAL ============================ */

    /** Aturan validasi berkas — dipakai bersama oleh unggah draf dan materi */
    protected function aturanBerkas(): array
    {
        return [
            'berkas' => [
                'required',
                'file',
                'max:'.(int) config('taskmate.max_file_kb', 20480),
                'mimes:'.config('taskmate.allowed_file_mimes'),
            ],
            'id_task' => ['nullable', 'integer'],
            'catatan_revisi' => ['nullable', 'string', 'max:255'],
        ];
    }

    protected function pesanBerkas(): array
    {
        $maksMb = round((int) config('taskmate.max_file_kb', 20480) / 1024);

        return [
            'berkas.required' => 'Pilih berkas yang akan diunggah.',
            'berkas.max' => 'Ukuran berkas melebihi batas maksimal '.$maksMb.' MB.',
            'berkas.mimes' => 'Format berkas tidak didukung. Gunakan: PDF, DOC/DOCX, PPT/PPTX, XLS/XLSX, TXT, ZIP, PNG, atau JPG.',
            'berkas.uploaded' => 'Unggahan gagal. Periksa ukuran berkas dan pengaturan server (php.ini).',
            'catatan_revisi.max' => 'Catatan maksimal 255 karakter.',
        ];
    }

    /**
     * Menyimpan berkas sebagai dokumen baru atau versi baru dari dokumen
     * bernama sama pada kategori yang sama.
     */
    protected function simpan(
        Request $request,
        Workspace $workspace,
        string $kategori,
        ?int $idTask,
        ?string $catatan,
        bool $isLeader
    ) {
        $user = $request->user();
        $errorBag = $kategori === FileDokumen::KATEGORI_MATERI ? 'materi' : 'default';

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
            ['id_workspace' => $workspace->id_workspace, 'kategori' => $kategori, 'nama_file' => $namaFile],
            ['dibuat_oleh' => $user->id_user, 'id_task' => $idTask]
        );

        // Member tidak boleh menambah versi pada draf yang terkait tugas anggota lain
        if (! $dokumen->wasRecentlyCreated && ! $isLeader && $dokumen->id_task) {
            $pjTugas = Task::whereKey($dokumen->id_task)->value('id_penanggung_jawab');
            if ((int) $pjTugas !== (int) $user->id_user) {
                return back()->withInput()->withErrors([
                    'berkas' => 'Berkas bernama "'.$namaFile.'" sudah terkait dengan tugas anggota lain. Ganti nama berkas Anda.',
                ], $errorBag);
            }
        }

        if ($idTask) {
            $dokumen->update(['id_task' => $idTask]);
        }

        $nomor = ((int) $dokumen->versions()->max('nomor_versi')) + 1;

        $path = $unggahan->storeAs(
            'files/workspace_'.$workspace->id_workspace.'/file_'.$dokumen->id_file,
            'v'.$nomor.'_'.$namaFile,
            'local'
        );

        if ($path === false) {
            if ($dokumen->wasRecentlyCreated) {
                $dokumen->delete();
            }

            return back()->withInput()->withErrors([
                'berkas' => 'Berkas gagal disimpan di server. Coba unggah kembali.',
            ], $errorBag);
        }

        FileVersion::create([
            'id_file' => $dokumen->id_file,
            'nomor_versi' => $nomor,
            'path_file' => $path,
            'ukuran_byte' => (int) $unggahan->getSize(),
            'catatan_revisi' => $catatan,
            'id_uploader' => $user->id_user,
            'waktu_unggah' => now(),
        ]);

        $jenis = $kategori === FileDokumen::KATEGORI_MATERI ? 'Materi' : 'Draf';

        return redirect()
            ->route('repository.index', [$workspace, 'tab' => $kategori])
            ->with('sukses', $jenis.' "'.$namaFile.'" tersimpan sebagai versi v'.$nomor.'.');
    }

    /**
     * Memastikan pemohon anggota workspace pemilik berkas dan berkas fisiknya
     * ada. Mengembalikan [FileDokumen, Filesystem disk].
     */
    protected function bukaVersi(Request $request, FileVersion $version): array
    {
        $file = $version->file()->with('workspace')->firstOrFail();
        $workspace = $file->workspace;

        abort_unless($workspace && $workspace->hasMember($request->user()->id_user), 403,
            'Anda bukan anggota workspace tempat berkas ini berada.');

        $disk = Storage::disk('local');
        abort_unless($disk->exists($version->path_file), 404, 'Berkas tidak ditemukan pada penyimpanan server.');

        return [$file, $disk];
    }
}