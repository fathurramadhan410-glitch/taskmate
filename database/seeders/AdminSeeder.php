<?php

namespace Database\Seeders;

use App\Models\SiakadApp;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Inisialisasi fitur Administrator + aplikasi bawaan halaman SIAKAD & E-Learning.
 * Jalankan: php artisan db:seed --class=AdminSeeder
 */
class AdminSeeder extends Seeder
{
    public function run(): void
    {
        /* ---------- 1) Akun administrator ---------- */
        $admin = User::firstOrCreate(
            ['email' => 'admin@taskmate.test'],
            [
                'nama' => 'Administrator TaskMate',
                'password' => 'Admin12345', // otomatis ter-hash lewat cast pada model User
                'role' => 'admin',
                'status_akun' => 'aktif',
            ]
        );

        // Kalau akun sudah ada sebelumnya, pastikan role-nya admin
        if (! $admin->isAdmin()) {
            $admin->forceFill(['role' => 'admin'])->save();
        }

        /* ---------- 2) Aplikasi bawaan SIAKAD & E-Learning ---------- */
        SiakadApp::firstOrCreate(
            ['url' => 'https://edlink.id/login?r=%2Fclasses&sso_attempt=1'],
            [
                'nama' => 'Edlink Sevima',
                'deskripsi' => 'E-learning & kuliah daring: materi perkuliahan, kuis, tugas, dan forum diskusi mata kuliah.',
                'tag' => 'E-Learning',
                'warna_tag' => 'cyan',
                'logo' => 'images/logo-edlink.png',
                'urutan' => 1,
                'aktif' => true,
            ]
        );

        SiakadApp::firstOrCreate(
            ['url' => 'https://sip.poliban.ac.id/gate/login'],
            [
                'nama' => 'SIP Poliban',
                'deskripsi' => 'Sistem informasi akademik: jadwal kuliah, KRS, KHS, transkrip nilai, dan status akun mahasiswa.',
                'tag' => 'Sistem Akademik',
                'warna_tag' => 'navy',
                'logo' => 'images/logo-poliban.png',
                'urutan' => 2,
                'aktif' => true,
            ]
        );
    }
}