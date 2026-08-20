<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['pengajuan_id', 'tipe', 'path', 'nama_asli', 'mime', 'uploaded_by'])]
class DokumenPengajuan extends Model
{
    public const TIPE_AWAL = ['ktp', 'spk', 'bukti_bayar', 'kk'];
    public const TIPE_TTD = ['signed_kontrak', 'signed_po'];

    public const LABELS = [
        'ktp' => 'KTP',
        'spk' => 'SPK',
        'bukti_bayar' => 'Bukti Bayar Tanda Jadi',
        'kk' => 'Kartu Keluarga',
        'form_aplikasi' => 'Form Aplikasi',
        'kontrak' => 'Dokumen Kontrak',
        'po' => 'Dokumen PO',
        'signed_kontrak' => 'Kontrak sudah TTD',
        'signed_po' => 'PO sudah TTD',
    ];

    public function pengajuan(): BelongsTo
    {
        return $this->belongsTo(Pengajuan::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function isImage(): bool
    {
        return str_starts_with((string) $this->mime, 'image/');
    }

    public static function label(string $tipe): string
    {
        return self::LABELS[$tipe] ?? $tipe;
    }
}
