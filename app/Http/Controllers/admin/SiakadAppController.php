<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;

class SiakadAppController extends Controller
{
    /**
     * Cache metadata kolom tabel siakad_applications.
     * Dipakai agar controller tetap aman walau skema tabel berubah.
     */
    private static ?array $cacheKolom = null;

    /* =====================================================================
     |  AKSI UTAMA
     * ==================================================================== */

    /**
     * Daftar aplikasi SIAKAD (GET /admin/siakad).
     */
    public function index()
    {
        $q = trim((string) request()->query('q', ''));

        $daftar = collect();

        try {
            $query = DB::table('siakad_applications');

            // Pencarian sederhana (aman: hanya kolom yang benar-benar ada).
            if ($q !== '') {
                $kolomCari = ['nama', 'url'];
                foreach (['tag', 'deskripsi'] as $kolomTambahan) {
                    if ($this->adaKolom($kolomTambahan)) {
                        $kolomCari[] = $kolomTambahan;
                    }
                }

                $query->where(function ($sub) use ($q, $kolomCari) {
                    foreach ($kolomCari as $kolom) {
                        $sub->orWhere($kolom, 'like', "%{$q}%");
                    }
                });
            }

            // Urutkan berdasarkan urutan (jika kolomnya ada), lalu id.
            if ($this->adaKolom('urutan')) {
                $query->orderByRaw('COALESCE(urutan, 999999) ASC');
            }
            $query->orderBy('id', 'ASC');

            $daftar = $query->get();
        } catch (\Throwable $e) {
            $daftar = collect();
        }

        $totalSemua = $daftar->count();

        // Beberapa alias nama variabel disediakan agar kompatibel dengan view
        // yang sudah ada (view boleh memakai nama variabel mana pun di daftar ini).
        return view('admin.siakad', [
            'aplikasi'       => $daftar,
            'aplikasis'      => $daftar,
            'daftarAplikasi' => $daftar,
            'daftar'         => $daftar,
            'siakad'         => $daftar,
            'siakadApps'     => $daftar,
            'apps'           => $daftar,
            'totalSemua'     => $totalSemua,
            'totalAplikasi'  => $totalSemua,
            'q'              => $q,
        ]);
    }

    /**
     * Alias untuk index() kalau route memanggil method "daftar".
     */
    public function daftar()
    {
        return $this->index();
    }

    /**
     * Simpan aplikasi baru (POST /admin/siakad → route admin.siakad.store).
     */
    public function store(Request $request)
    {
        $validasi = Validator::make($request->all(), [
            'nama'      => ['required', 'string', 'max:255'],
            'url'       => ['required', 'string', 'max:500'],
            'deskripsi' => ['nullable', 'string', 'max:2000'],
            'urutan'    => ['nullable', 'integer', 'min:0'],
            'logo'      => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,gif,svg', 'max:4096'],
        ], [
            'required' => ':attribute wajib diisi.',
            'max'      => ':attribute terlalu panjang atau terlalu besar.',
            'image'    => 'Logo harus berupa gambar (JPG/PNG/WEBP/GIF/SVG).',
            'mimes'    => 'Format logo harus JPG, PNG, WEBP, GIF, atau SVG.',
            'integer'  => 'Urutan harus berupa angka.',
        ]);

        if ($validasi->fails()) {
            return back()
                ->withErrors($validasi)
                ->withInput()
                ->with('gagal', 'Periksa kembali isian form: ' . $validasi->errors()->first());
        }

        if ($request->hasFile('logo') && !$request->file('logo')->isValid()) {
            return back()
                ->withInput()
                ->with('gagal', 'Logo gagal diunggah. Periksa ukuran file (maksimal 4 MB) dan formatnya (JPG/PNG/WEBP/GIF/SVG).');
        }

        $logo = $this->simpanLogo($request);

        // Urutan otomatis: kalau form tidak mengisi urutan, pakai (max urutan) + 1.
        $urutan = $request->filled('urutan')
            ? (int) $request->input('urutan')
            : ($this->adaKolom('urutan') ? ((int) DB::table('siakad_applications')->max('urutan') + 1) : null);

        $data = $this->dataAplikasi($request, $logo, $urutan, true);

        try {
            DB::table('siakad_applications')->insert($data);
        } catch (\Throwable $e) {
            // Insert gagal → hapus logo yang sudah terunggah agar tidak jadi file yatim.
            $this->hapusFileLogo($logo);

            return redirect()->route('admin.siakad')
                ->with('gagal', 'Gagal menyimpan aplikasi SIAKAD: ' . $e->getMessage());
        }

        return redirect()->route('admin.siakad')
            ->with('sukses', 'Aplikasi SIAKAD berhasil ditambahkan.');
    }

    /**
     * Alias untuk store() kalau route memanggil method "simpan".
     */
    public function simpan(Request $request)
    {
        return $this->store($request);
    }

    /**
     * Perbarui aplikasi (POST /admin/siakad/{app}/update).
     */
    public function update(Request $request, $app)
    {
        $aplikasi = DB::table('siakad_applications')->where('id', $app)->first();

        if (!$aplikasi) {
            return redirect()->route('admin.siakad')
                ->with('gagal', 'Aplikasi SIAKAD tidak ditemukan.');
        }

        $validasi = Validator::make($request->all(), [
            'nama'      => ['required', 'string', 'max:255'],
            'url'       => ['required', 'string', 'max:500'],
            'deskripsi' => ['nullable', 'string', 'max:2000'],
            'urutan'    => ['nullable', 'integer', 'min:0'],
            'logo'      => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,gif,svg', 'max:4096'],
        ], [
            'required' => ':attribute wajib diisi.',
            'max'      => ':attribute terlalu panjang atau terlalu besar.',
            'image'    => 'Logo harus berupa gambar (JPG/PNG/WEBP/GIF/SVG).',
            'mimes'    => 'Format logo harus JPG, PNG, WEBP, GIF, atau SVG.',
            'integer'  => 'Urutan harus berupa angka.',
        ]);

        if ($validasi->fails()) {
            return back()
                ->withErrors($validasi)
                ->withInput()
                ->with('gagal', 'Periksa kembali isian form: ' . $validasi->errors()->first());
        }

        if ($request->hasFile('logo') && !$request->file('logo')->isValid()) {
            return back()
                ->withInput()
                ->with('gagal', 'Logo gagal diunggah. Periksa ukuran file (maksimal 4 MB) dan formatnya (JPG/PNG/WEBP/GIF/SVG).');
        }

        $logoBaru = $this->simpanLogo($request);

        // Kalau form tidak mengisi urutan, nilai urutan lama dipertahankan.
        $urutan = $request->filled('urutan') ? (int) $request->input('urutan') : null;

        $data = $this->dataAplikasi($request, $logoBaru, $urutan, false);

        try {
            DB::table('siakad_applications')->where('id', $app)->update($data);
        } catch (\Throwable $e) {
            $this->hapusFileLogo($logoBaru);

            return redirect()->route('admin.siakad')
                ->with('gagal', 'Gagal memperbarui aplikasi SIAKAD: ' . $e->getMessage());
        }

        // Update sudah berhasil → kalau logo diganti, rapikan file logo lama.
        if (!empty($data['logo']) && !empty($aplikasi->logo) && $data['logo'] !== $aplikasi->logo) {
            $this->hapusFileLogo($aplikasi->logo);
        }

        return redirect()->route('admin.siakad')
            ->with('sukses', 'Aplikasi SIAKAD berhasil diperbarui.');
    }

    /**
     * Alias untuk update() kalau route memanggil method "perbarui".
     */
    public function perbarui(Request $request, $app)
    {
        return $this->update($request, $app);
    }

    /**
     * Hapus aplikasi (POST /admin/siakad/{app}/hapus atau DELETE /admin/siakad/{app}).
     */
    public function destroy($app)
    {
        $aplikasi = DB::table('siakad_applications')->where('id', $app)->first();

        if (!$aplikasi) {
            return redirect()->route('admin.siakad')
                ->with('gagal', 'Aplikasi SIAKAD tidak ditemukan.');
        }

        try {
            DB::table('siakad_applications')->where('id', $app)->delete();
        } catch (\Throwable $e) {
            return redirect()->route('admin.siakad')
                ->with('gagal', 'Gagal menghapus aplikasi SIAKAD: ' . $e->getMessage());
        }

        // Hapus sudah sukses → rapikan file logo milik aplikasi tersebut.
        $this->hapusFileLogo($aplikasi->logo);

        return redirect()->route('admin.siakad')
            ->with('sukses', 'Aplikasi SIAKAD berhasil dihapus.');
    }

    /**
     * Alias untuk destroy() kalau route memanggil method "hapus".
     */
    public function hapus($app)
    {
        return $this->destroy($app);
    }

    /* =====================================================================
     |  HELPER INTERNAL
     * ==================================================================== */

    /**
     * Susun data untuk insert/update berdasarkan input form.
     * Hanya kolom yang benar-benar ada di tabel yang diikutkan.
     */
    private function dataAplikasi(Request $request, ?string $logo, ?int $urutan, bool $dataBaru): array
    {
        $data = [];

        $data['nama'] = trim((string) $request->input('nama', ''));
        $data['url']  = trim((string) $request->input('url', ''));

        // Deskripsi — simpan hanya jika kolomnya ada di tabel.
        if ($request->has('deskripsi') && $this->adaKolom('deskripsi')) {
            $data['deskripsi'] = trim((string) $request->input('deskripsi', ''));
        }

        // Tag/kategori — simpan hanya jika kolomnya ada dan form mengirimnya.
        $tag = trim((string) ($request->input('tag') ?? $request->input('kategori') ?? ''));
        if ($tag !== '' && $this->adaKolom('tag')) {
            $data['tag'] = $tag;
        }

        // Urutan.
        if (!is_null($urutan) && $this->adaKolom('urutan')) {
            $data['urutan'] = (int) $urutan;
        }

        // Logo.
        if (!empty($logo) && $this->adaKolom('logo')) {
            $data['logo'] = $logo;
        }

        if ($this->adaKolom('updated_at')) {
            $data['updated_at'] = now();
        }

        // Untuk data baru: pastikan semua kolom NOT NULL tanpa default ikut terisi
        // (ini yang mencegah error 1364 "Field 'tag' doesn't have a default value").
        if ($dataBaru) {
            $data = $this->lengkapiKolomWajib($data);
        }

        return $data;
    }

    /**
     * Pastikan semua kolom NOT NULL tanpa nilai default mendapatkan nilai saat
     * insert (mencegah error 1364 "Field ... doesn't have a default value").
     */
    private function lengkapiKolomWajib(array $data): array
    {
        foreach ($this->kolomAplikasi() as $kolom) {
            $nama = (string) ($kolom->name ?? '');

            if ($nama === '' || $nama === 'id' || array_key_exists($nama, $data)) {
                continue;
            }

            $extra = strtolower((string) ($kolom->extra ?? ''));

            // Lewati kolom auto_increment dan kolom hasil generate.
            if (str_contains($extra, 'auto_increment') || str_contains($extra, 'generated always')) {
                continue;
            }

            // Lewati kolom yang boleh NULL atau sudah punya nilai default.
            $bolehNull    = strtoupper((string) ($kolom->is_nullable ?? 'YES')) === 'YES';
            $punyaDefault = !is_null($kolom->column_default ?? null);

            if ($bolehNull || $punyaDefault) {
                continue;
            }

            $data[$nama] = $this->nilaiIsiWajib($kolom);
        }

        return $data;
    }

    /**
     * Nilai pengganti untuk kolom wajib yang tidak diisi form.
     */
    private function nilaiIsiWajib(object $kolom)
    {
        $tipe      = strtolower((string) ($kolom->data_type ?? ''));
        $tipePenuh = strtolower((string) ($kolom->column_type ?? ''));
        $nama      = strtolower((string) ($kolom->name ?? ''));

        // Kolom angka.
        if (in_array($tipe, ['int', 'bigint', 'smallint', 'mediumint', 'tinyint', 'decimal', 'numeric', 'float', 'double'], true)) {
            return 0;
        }

        // Kolom tanggal/waktu.
        if (in_array($tipe, ['date', 'datetime', 'timestamp'], true)) {
            return now();
        }

        // Kolom enum/set: pakai anggota pertama agar nilainya selalu valid.
        if (in_array($tipe, ['enum', 'set'], true) && preg_match('/^(enum|set)\((.*)\)$/i', $tipePenuh, $m)) {
            $anggota = str_getcsv($m[2], ',', "'");
            if (!empty($anggota) && $anggota[0] !== null && $anggota[0] !== '') {
                return $anggota[0];
            }
        }

        // Kolom teks: pakai nilai default yang wajar bila dikenal.
        return $this->nilaiDefaultTeks($nama);
    }

    /**
     * Peta nilai default yang wajar untuk kolom teks wajib.
     */
    private function nilaiDefaultTeks(string $namaKolom): string
    {
        $peta = [
            'tag'      => 'Umum',
            'kategori' => 'umum',
            'status'   => 'aktif',
            'tipe'     => 'umum',
            'jenis'    => 'umum',
        ];

        return $peta[$namaKolom] ?? '';
    }

    /**
     * Simpan file logo ke public/uploads/siakad.
     * Mengembalikan path relatif (mis. "uploads/siakad/169..._logo.png") atau null.
     */
    private function simpanLogo(Request $request): ?string
    {
        if (!$request->hasFile('logo') || !$request->file('logo')->isValid()) {
            return null;
        }

        $berkas     = $request->file('logo');
        $namaAsli   = pathinfo($berkas->getClientOriginalName(), PATHINFO_FILENAME);
        $ekstensi   = strtolower($berkas->getClientOriginalExtension() ?: 'png');
        $namaBersih = preg_replace('/[^A-Za-z0-9\-_]/', '_', $namaAsli);
        $namaBersih = trim((string) $namaBersih, '_');

        if ($namaBersih === '') {
            $namaBersih = 'logo';
        }

        $namaBerkas = time() . '_' . $namaBersih . '.' . $ekstensi;
        $folder     = public_path('uploads/siakad');

        if (!is_dir($folder)) {
            @mkdir($folder, 0775, true);
        }

        $berkas->move($folder, $namaBerkas);

        return 'uploads/siakad/' . $namaBerkas;
    }

    /**
     * Hapus file logo di public/uploads/siakad (hanya di folder itu, supaya aman).
     */
    private function hapusFileLogo(?string $pathLogo): void
    {
        if (empty($pathLogo)) {
            return;
        }

        if (!str_starts_with($pathLogo, 'uploads/siakad/')) {
            return;
        }

        $pathLengkap = public_path($pathLogo);

        if (is_file($pathLengkap)) {
            @unlink($pathLengkap);
        }
    }

    /**
     * Metadata kolom tabel siakad_applications (dari information_schema).
     */
    private function kolomAplikasi(): array
    {
        if (self::$cacheKolom !== null) {
            return self::$cacheKolom;
        }

        try {
            self::$cacheKolom = DB::select(
                "SELECT column_name AS `name`, data_type AS `data_type`, column_type AS `column_type`,
                        is_nullable AS `is_nullable`, column_default AS `column_default`, extra AS `extra`
                 FROM information_schema.columns
                 WHERE table_schema = DATABASE()
                   AND table_name = 'siakad_applications'
                 ORDER BY ordinal_position ASC"
            );
        } catch (\Throwable $e) {
            self::$cacheKolom = [];
        }

        return self::$cacheKolom;
    }

    /**
     * Daftar nama kolom tabel siakad_applications.
     */
    private function namaKolom(): array
    {
        $daftarNama = [];

        foreach ($this->kolomAplikasi() as $kolom) {
            $daftarNama[] = (string) ($kolom->name ?? '');
        }

        $daftarNama = array_values(array_filter($daftarNama));

        if (!empty($daftarNama)) {
            return $daftarNama;
        }

        // Cadangan kalau information_schema gagal.
        try {
            return Schema::getColumnListing('siakad_applications');
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Cek apakah sebuah kolom ada di tabel siakad_applications.
     */
    private function adaKolom(string $namaKolom): bool
    {
        $semuaKolom = $this->namaKolom();

        if (empty($semuaKolom)) {
            return false;
        }

        return in_array($namaKolom, $semuaKolom, true);
    }
}