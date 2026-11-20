<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Daftar aplikasi kampus yang muncul di halaman SIAKAD & E-Learning.
 * Dikelola oleh administrator lewat menu "Aplikasi SIAKAD".
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('siakad_apps', function (Blueprint $table) {
            $table->id('id_siakad_app');
            $table->string('nama', 100);
            $table->string('url', 255);
            $table->string('deskripsi', 255)->nullable();
            $table->string('tag', 50)->nullable();          // label singkat, mis. "E-Learning"
            $table->string('warna_tag', 20)->default('cyan'); // cyan | navy
            $table->string('logo')->nullable();             // nama file di images/siakad/ atau path images/...
            $table->unsignedInteger('urutan')->default(0);
            $table->boolean('aktif')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('siakad_apps');
    }
};