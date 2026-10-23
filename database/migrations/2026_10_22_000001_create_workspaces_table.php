<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workspaces', function (Blueprint $table) {
            $table->bigIncrements('id_workspace');
            $table->string('nama_workspace', 100);
            $table->string('mata_kuliah', 100)->nullable();
            $table->text('deskripsi')->nullable();
            $table->string('kode_undangan', 12)->unique();
            $table->boolean('undangan_aktif')->default(true);
            $table->timestamp('undangan_berlaku_sampai')->nullable();
            $table->unsignedBigInteger('id_pembuat')->nullable();
            $table->foreign('id_pembuat')->references('id_user')->on('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workspaces');
    }
};
