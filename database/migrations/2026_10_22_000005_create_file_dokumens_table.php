<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('file_dokumens', function (Blueprint $table) {
            $table->bigIncrements('id_file');
            $table->unsignedBigInteger('id_workspace');
            $table->unsignedBigInteger('id_task')->nullable();
            $table->string('nama_file', 150);
            $table->unsignedBigInteger('dibuat_oleh')->nullable();
            $table->timestamps();

            $table->foreign('id_workspace')->references('id_workspace')->on('workspaces')->cascadeOnDelete();
            $table->foreign('id_task')->references('id_task')->on('tasks')->nullOnDelete();
            $table->foreign('dibuat_oleh')->references('id_user')->on('users')->nullOnDelete();
            $table->unique(['id_workspace', 'nama_file']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('file_dokumens');
    }
};
