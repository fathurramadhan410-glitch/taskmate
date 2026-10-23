<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifikasis', function (Blueprint $table) {
            $table->bigIncrements('id_notif');
            $table->unsignedBigInteger('id_user');
            $table->unsignedBigInteger('id_task')->nullable();
            $table->enum('jenis', ['penugasan', 'perubahan', 'h3', 'h1', 'hari_h', 'terlambat', 'review', 'bergabung'])->default('penugasan');
            $table->string('pesan', 255);
            $table->boolean('status_baca')->default(false);
            $table->enum('status_email', ['terkirim', 'gagal', 'menunggu'])->default('menunggu');
            $table->dateTime('waktu_kirim');

            $table->foreign('id_user')->references('id_user')->on('users')->cascadeOnDelete();
            $table->foreign('id_task')->references('id_task')->on('tasks')->nullOnDelete();
            $table->index(['id_user', 'status_baca']);
            $table->index(['id_task', 'jenis']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifikasis');
    }
};
