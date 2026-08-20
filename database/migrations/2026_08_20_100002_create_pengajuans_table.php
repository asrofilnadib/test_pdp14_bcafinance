<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pengajuans', function (Blueprint $table) {
            $table->id();
            $table->string('nomor')->unique();
            $table->string('status', 30)->default('draft')->index();
            $table->foreignId('dealer_id')->constrained('dealers');
            $table->foreignId('marketing_id')->nullable()->constrained('users');

            $table->string('konsumen_nama');
            $table->string('konsumen_nik', 16);
            $table->date('konsumen_tgl_lahir')->nullable();
            $table->string('status_perkawinan', 20)->nullable();
            $table->string('data_pasangan')->nullable();

            $table->string('merk_kendaraan')->nullable();
            $table->string('model_kendaraan')->nullable();
            $table->string('tipe_kendaraan')->nullable();
            $table->string('warna_kendaraan')->nullable();
            $table->decimal('harga_kendaraan', 15, 2)->nullable();

            $table->string('asuransi')->nullable();
            $table->decimal('down_payment', 15, 2)->nullable();
            $table->unsignedTinyInteger('lama_kredit')->nullable();
            $table->decimal('angsuran', 15, 2)->nullable();

            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->timestamp('approved_at')->nullable();
            $table->text('catatan_approval')->nullable();

            $table->foreignId('disbursed_by')->nullable()->constrained('users');
            $table->timestamp('disbursed_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('konsumen_nama');
            $table->index('konsumen_nik');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengajuans');
    }
};
