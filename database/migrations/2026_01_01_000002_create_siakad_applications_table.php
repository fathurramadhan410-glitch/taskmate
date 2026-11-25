<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('siakad_applications')) {
            return;
        }

        Schema::create('siakad_applications', function (Blueprint $table) {
            $table->id();
            $table->string('nama', 100);
            $table->string('tag', 40);
            $table->string('warna', 10)->default('cyan');
            $table->text('caption');
            $table->string('url', 255);
            $table->string('logo')->nullable();
            $table->unsignedInteger('urutan')->default(0);
            $table->boolean('aktif')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('siakad_applications');
    }
};