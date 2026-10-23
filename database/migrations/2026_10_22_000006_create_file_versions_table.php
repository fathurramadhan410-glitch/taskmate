<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('file_versions', function (Blueprint $table) {
            $table->bigIncrements('id_version');
            $table->unsignedBigInteger('id_file');
            $table->unsignedInteger('nomor_versi');
            $table->string('path_file', 255);
            $table->unsignedBigInteger('ukuran_byte')->default(0);
            $table->string('catatan_revisi', 255)->nullable();
            $table->unsignedBigInteger('id_uploader')->nullable();
            $table->dateTime('waktu_unggah');

            $table->foreign('id_file')->references('id_file')->on('file_dokumens')->cascadeOnDelete();
            $table->foreign('id_uploader')->references('id_user')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('file_versions');
    }
};
