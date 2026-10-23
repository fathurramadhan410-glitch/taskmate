<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workspace_members', function (Blueprint $table) {
            $table->bigIncrements('id_member');
            $table->unsignedBigInteger('id_workspace');
            $table->unsignedBigInteger('id_user');
            $table->enum('peran', ['leader', 'member'])->default('member');
            $table->timestamp('tanggal_bergabung')->nullable();
            $table->unique(['id_workspace', 'id_user']);

            $table->foreign('id_workspace')->references('id_workspace')->on('workspaces')->cascadeOnDelete();
            $table->foreign('id_user')->references('id_user')->on('users')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workspace_members');
    }
};
