<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiakadApplication;
use Illuminate\Http\Request;

class SiakadAppController extends Controller
{
    public function index(Request $request)
    {
        $aplikasi = SiakadApplication::orderBy('urutan')->orderBy('nama')->get();
        $diedit   = $request->query('edit') ? SiakadApplication::find($request->query('edit')) : null;

        return view('admin.siakad', compact('aplikasi', 'diedit'));
    }

    public function store(Request $request)
    {
        $data = $this->validasi($request);

        $app = new SiakadApplication();
        $app->nama    = $data['nama'];
        $app->tag     = $data['tag'];
        $app->warna   = $data['warna'];
        $app->caption = $data['caption'];
        $app->url     = $data['url'];
        $app->urutan  = $data['urutan'];
        $app->aktif   = $request->boolean('aktif');
        $app->logo    = $this->simpanLogo($request);
        $app->save();

        return redirect()->route('admin.siakad')
            ->with('success', 'Aplikasi "' . $app->nama . '" berhasil ditambahkan dan tampil di menu SIAKAD & E-Learning.');
    }

    public function update(Request $request, SiakadApplication $app)
    {
        $data = $this->validasi($request);

        $app->nama    = $data['nama'];
        $app->tag     = $data['tag'];
        $app->warna   = $data['warna'];
        $app->caption = $data['caption'];
        $app->url     = $data['url'];
        $app->urutan  = $data['urutan'];
        $app->aktif   = $request->boolean('aktif');

        if ($request->hasFile('logo')) {
            $this->hapusFileLogo($app->logo);
            $app->logo = $this->simpanLogo($request);
        }

        $app->save();

        return redirect()->route('admin.siakad')
            ->with('success', 'Aplikasi "' . $app->nama . '" berhasil diperbarui.');
    }

    public function destroy(SiakadApplication $app)
    {
        $this->hapusFileLogo($app->logo);
        $nama = $app->nama;
        $app->delete();

        return redirect()->route('admin.siakad')
            ->with('success', 'Aplikasi "' . $nama . '" berhasil dihapus.');
    }

    private function validasi(Request $request): array
    {
        return $request->validate([
            'nama'    => ['required', 'string', 'max:100'],
            'tag'     => ['required', 'string', 'max:40'],
            'warna'   => ['required', 'in:cyan,navy'],
            'caption' => ['required', 'string', 'max:300'],
            'url'     => ['required', 'url', 'max:255'],
            'logo'    => ['nullable', 'image', 'max:2048'],
            'urutan'  => ['required', 'integer', 'min:0'],
        ]);
    }

    private function simpanLogo(Request $request): ?string
    {
        if (! $request->hasFile('logo')) {
            return null;
        }

        $folder = public_path('images/siakad');
        if (! is_dir($folder)) {
            mkdir($folder, 0755, true);
        }

        $file = $request->file('logo');
        $nama = 'siakad-' . uniqid() . '.' . strtolower($file->getClientOriginalExtension());
        $file->move($folder, $nama);

        return 'images/siakad/' . $nama;
    }

    private function hapusFileLogo(?string $logo): void
    {
        // Hanya hapus file yang memang diupload admin (folder images/siakad),
        // logo bawaan di public/images tidak disentuh.
        if ($logo && str_starts_with($logo, 'images/siakad/') && file_exists(public_path($logo))) {
            @unlink(public_path($logo));
        }
    }
}