<?php

namespace Database\Seeders;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // ============================================================
        // 0. PERBAIKAN STRUKTUR DATABASE (tanpa perlu migration)
        // ============================================================

        // --- a. Pastikan kolom admin system ada di tabel users ---
        if (Schema::hasTable('users')) {

            $adaKolomBaru = false;

            Schema::table('users', function (Blueprint $table) use (&$adaKolomBaru) {
                if (! Schema::hasColumn('users', 'role')) {
                    $table->string('role', 20)->default('mahasiswa');
                    $adaKolomBaru = true;
                }
                if (! Schema::hasColumn('users', 'status')) {
                    $table->string('status', 20)->default('aktif');
                    $adaKolomBaru = true;
                }
                if (! Schema::hasColumn('users', 'gagal_login')) {
                    $table->unsignedInteger('gagal_login')->default(0);
                    $adaKolomBaru = true;
                }
                if (! Schema::hasColumn('users', 'diblokir_pada')) {
                    $table->timestamp('diblokir_pada')->nullable();
                    $adaKolomBaru = true;
                }
                if (! Schema::hasColumn('users', 'alasan_blokir')) {
                    $table->string('alasan_blokir')->nullable();
                    $adaKolomBaru = true;
                }
            });

            if ($adaKolomBaru) {
                $this->command->info('Kolom admin system ditambahkan ke tabel users.');
            }

            // --- b. Deteksi otomatis kolom "nama" di tabel users ---
            $kolomNama = null;
            foreach (['name', 'nama', 'username', 'nama_lengkap', 'full_name'] as $coba) {
                if (Schema::hasColumn('users', $coba)) {
                    $kolomNama = $coba;
                    break;
                }
            }

            // Info struktur database (berguna untuk penyesuaian kode)
            $this->command->info('Kolom di tabel users: ' . implode(', ', Schema::getColumnListing('users')));
            $this->command->info('Kolom nama terdeteksi: ' . ($kolomNama ?? '(tidak ada)'));

            // --- c. Isi role & status untuk akun yang masih kosong ---
            DB::table('users')->whereNull('role')->update(['role' => 'mahasiswa']);
            DB::table('users')->whereNull('status')->update(['status' => 'aktif']);

            // --- d. Buat akun admin (kalau belum ada) ---
            // >>> GANTI email & password di bawah ini kalau mau <<<
            $adminEmail = 'admin@taskmate.id';
            $adminSandi = 'admin12345';

            $adminSudahAda = DB::table('users')->where('email', $adminEmail)->exists();

            if (! $adminSudahAda) {
                $dataAdmin = [
                    'email'      => $adminEmail,
                    'password'   => Hash::make($adminSandi),
                    'role'       => 'admin',
                    'status'     => 'aktif',
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                if ($kolomNama !== null) {
                    $dataAdmin[$kolomNama] = 'Admin TaskMate';
                }

                DB::table('users')->insert($dataAdmin);

                $this->command->info('Akun admin berhasil dibuat (' . $adminEmail . ' / ' . $adminSandi . ')');
            } else {
                DB::table('users')->where('email', $adminEmail)->update([
                    'role'   => 'admin',
                    'status' => 'aktif',
                ]);

                $this->command->info('Akun admin sudah ada, dipastikan role-nya admin.');
            }
        } else {
            $this->command->warn('Tabel users tidak ditemukan — bagian akun dilewati.');
        }

        // ============================================================
        // 1. TABEL siakad_applications — dibuat kalau belum ada
        // ============================================================
        if (! Schema::hasTable('siakad_applications')) {
            Schema::create('siakad_applications', function (Blueprint $table) {
                $table->id();
                $table->string('nama', 100);
                $table->string('tag', 40);
                $table->string('warna', 10)->default('cyan');
                $table->text('caption')->nullable();
                $table->string('url', 255);
                $table->string('logo')->nullable();
                $table->unsignedInteger('urutan')->default(0);
                $table->boolean('aktif')->default(true);
                $table->timestamps();
            });

            $this->command->info('Tabel siakad_applications berhasil dibuat.');
        }

        // ============================================================
        // 2. APLIKASI SIAKAD & E-LEARNING BAWAAN
        // ============================================================
        $aplikasiBawaan = [
            [
                'nama'    => 'Edlink Sevima',
                'tag'     => 'E-Learning',
                'warna'   => 'cyan',
                'caption' => 'Platform e-learning untuk materi, tugas, dan diskusi kelas online.',
                'url'     => 'https://edlink.sevima.com',
                'logo'    => 'images/logo-edlink.png',
                'urutan'  => 1,
            ],
            [
                'nama'    => 'SIP Poliban',
                'tag'     => 'Sistem Akademik',
                'warna'   => 'navy',
                'caption' => 'Sistem informasi akademik Poliban untuk nilai, jadwal, dan KRS.',
                'url'     => 'https://sip.poliban.ac.id',
                'logo'    => 'images/logo-poliban.png',
                'urutan'  => 2,
            ],
        ];

        foreach ($aplikasiBawaan as $data) {
            $sudahAda = DB::table('siakad_applications')->where('url', $data['url'])->exists();

            if (! $sudahAda) {
                DB::table('siakad_applications')->insert(array_merge($data, [
                    'aktif'      => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]));
            }
        }

        $this->command->info('Aplikasi SIAKAD & E-Learning bawaan sudah siap.');

        // ============================================================
        // SELESAI
        // ============================================================
        $this->command->info('SEEDER SELESAI — database TaskMate sudah siap.');
    }
}