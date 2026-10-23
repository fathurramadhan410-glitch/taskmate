<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->bigIncrements('id_task');
            $table->unsignedBigInteger('id_workspace');
            $table->string('judul', 150);
            $table->text('deskripsi')->nullable();
            $table->enum('status', ['todo', 'in_progress', 'review', 'done'])->default('todo');
            $table->dateTime('deadline');
            $table->unsignedBigInteger('id_penanggung_jawab')->nullable();
            $table->unsignedBigInteger('dibuat_oleh')->nullable();
            $table->timestamps();

            $table->foreign('id_workspace')->references('id_workspace')->on('workspaces')->cascadeOnDelete();
            $table->foreign('id_penanggung_jawab')->references('id_user')->on('users')->nullOnDelete();
            $table->foreign('dibuat_oleh')->references('id_user')->on('users')->nullOnDelete();
            $table->index(['id_workspace', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
