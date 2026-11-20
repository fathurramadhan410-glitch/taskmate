<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fitur administrator:
 * - role        : 'admin' | 'anggota' (default)
 * - status_akun : 'aktif' (default) | 'diblokir' (tidak bisa login sampai admin membukanya)
 * - blokir_alasan : keterangan kenapa akun diblokir (mis. salah sandi berulang / diblokir manual)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('anggota')->after('avatar');
            $table->string('status_akun')->default('aktif')->after('role');
            $table->string('blokir_alasan')->nullable()->after('status_akun');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role', 'status_akun', 'blokir_alasan']);
        });
    }
};