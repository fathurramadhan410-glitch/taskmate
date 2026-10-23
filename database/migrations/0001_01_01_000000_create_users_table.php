<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->bigIncrements('id_user');
            $table->string('nama', 100);
            $table->string('email', 100)->unique();
            $table->string('password')->nullable(); // null untuk akun yang hanya login via Google
            $table->string('google_id')->nullable()->unique();
            $table->string('avatar')->nullable();   // path lokal atau URL avatar Google
            $table->unsignedInteger('gagal_login')->default(0);
            $table->timestamp('terkunci_sampai')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
