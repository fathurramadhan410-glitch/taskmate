<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Memisahkan isi repository menjadi dua kategori:
 *  - "draf"   : berkas pekerjaan tugas (diunggah anggota / leader)
 *  - "materi" : materi & referensi (hanya diunggah Project Leader)
 *
 * Berkas lama otomatis menjadi kategori "draf". Nama berkas kini unik per
 * kategori, sehingga sebuah draf dan materi boleh memakai nama yang sama.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('file_dokumens', function (Blueprint $table) {
            $table->string('kategori', 10)->default('draf')->after('id_task');
        });

        // Indeks unik baru dibuat lebih dulu agar foreign key id_workspace
        // selalu tetap memiliki indeks pendukung saat indeks lama dihapus.
        Schema::table('file_dokumens', function (Blueprint $table) {
            $table->unique(['id_workspace', 'kategori', 'nama_file'], 'file_dokumens_ws_kategori_nama_unique');
        });

        Schema::table('file_dokumens', function (Blueprint $table) {
            $table->dropUnique(['id_workspace', 'nama_file']);
        });
    }

    public function down(): void
    {
        Schema::table('file_dokumens', function (Blueprint $table) {
            $table->unique(['id_workspace', 'nama_file']);
        });

        Schema::table('file_dokumens', function (Blueprint $table) {
            $table->dropUnique('file_dokumens_ws_kategori_nama_unique');
        });

        Schema::table('file_dokumens', function (Blueprint $table) {
            $table->dropColumn('kategori');
        });
    }
};