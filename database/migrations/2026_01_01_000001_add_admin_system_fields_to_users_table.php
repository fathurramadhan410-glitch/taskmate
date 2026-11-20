<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Semua kolom diberi pengecekan dulu, jadi migration ini
        // aman dijalankan walau sebagian kolom sudah ada.

        if (! Schema::hasColumn('users', 'role')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('role', 20)->default('mahasiswa');
            });
        }

        if (! Schema::hasColumn('users', 'status')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('status', 20)->default('aktif');
            });
        }

        if (! Schema::hasColumn('users', 'gagal_login')) {
            Schema::table('users', function (Blueprint $table) {
                $table->unsignedInteger('gagal_login')->default(0);
            });
        }

        if (! Schema::hasColumn('users', 'diblokir_pada')) {
            Schema::table('users', function (Blueprint $table) {
                $table->timestamp('diblokir_pada')->nullable();
            });
        }

        if (! Schema::hasColumn('users', 'alasan_blokir')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('alasan_blokir')->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'alasan_blokir')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('alasan_blokir');
            });
        }

        if (Schema::hasColumn('users', 'diblokir_pada')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('diblokir_pada');
            });
        }

        if (Schema::hasColumn('users', 'gagal_login')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('gagal_login');
            });
        }

        if (Schema::hasColumn('users', 'status')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('status');
            });
        }

        if (Schema::hasColumn('users', 'role')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('role');
            });
        }
    }
};