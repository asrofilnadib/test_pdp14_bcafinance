<?php

use App\Models\Pengajuan;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pengajuans', function (Blueprint $table) {
            $table->string('public_id', 6)->nullable()->after('id');
        });

        Pengajuan::query()->withTrashed()->whereNull('public_id')->each(function (Pengajuan $pengajuan): void {
            $pengajuan->forceFill([
                'public_id' => Pengajuan::generatePublicId(),
            ])->saveQuietly();
        });

        Schema::table('pengajuans', function (Blueprint $table) {
            $table->unique('public_id');
        });
    }

    public function down(): void
    {
        Schema::table('pengajuans', function (Blueprint $table) {
            $table->dropUnique(['public_id']);
            $table->dropColumn('public_id');
        });
    }
};
