<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('task_status_logs', function (Blueprint $table) {
            $table->bigIncrements('id_log');
            $table->unsignedBigInteger('id_task');
            $table->unsignedBigInteger('id_user')->nullable();
            $table->enum('status_lama', ['todo', 'in_progress', 'review', 'done'])->nullable();
            $table->enum('status_baru', ['todo', 'in_progress', 'review', 'done']);
            $table->string('catatan', 255)->nullable();
            $table->dateTime('waktu');

            $table->foreign('id_task')->references('id_task')->on('tasks')->cascadeOnDelete();
            $table->foreign('id_user')->references('id_user')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_status_logs');
    }
};
