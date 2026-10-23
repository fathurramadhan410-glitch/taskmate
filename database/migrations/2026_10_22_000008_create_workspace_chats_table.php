<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workspace_chats', function (Blueprint $table) {
            $table->bigIncrements('id_chat');
            $table->unsignedBigInteger('id_workspace');
            $table->unsignedBigInteger('id_user')->nullable();
            $table->text('pesan');
            $table->timestamps();

            $table->foreign('id_workspace')->references('id_workspace')->on('workspaces')->cascadeOnDelete();
            $table->foreign('id_user')->references('id_user')->on('users')->nullOnDelete();
            $table->index(['id_workspace', 'id_chat']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workspace_chats');
    }
};
