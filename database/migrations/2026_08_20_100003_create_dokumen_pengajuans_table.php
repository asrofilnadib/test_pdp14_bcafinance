<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dokumen_pengajuans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pengajuan_id')->constrained('pengajuans')->cascadeOnDelete();
            $table->string('tipe', 40);
            $table->string('path');
            $table->string('nama_asli');
            $table->string('mime', 80)->nullable();
            $table->foreignId('uploaded_by')->constrained('users');
            $table->timestamps();

            $table->index(['pengajuan_id', 'tipe']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dokumen_pengajuans');
    }
};
