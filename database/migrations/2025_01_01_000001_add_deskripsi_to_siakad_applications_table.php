<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Menambahkan kolom `deskripsi` pada tabel siakad_applications bila belum ada,
 * agar isian "Deskripsi" dari form admin ikut tersimpan.
 * Aman dijalankan kapan saja: ada pengecekan tabel dan kolom di dalamnya.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('siakad_applications')) {
            return;
        }

        if (Schema::hasColumn('siakad_applications', 'deskripsi')) {
            return;
        }

        Schema::table('siakad_applications', function (Blueprint $table) {
            $table->text('deskripsi')->nullable()->after('url');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('siakad_applications')) {
            return;
        }

        if (!Schema::hasColumn('siakad_applications', 'deskripsi')) {
            return;
        }

        Schema::table('siakad_applications', function (Blueprint $table) {
            $table->dropColumn('deskripsi');
        });
    }
};