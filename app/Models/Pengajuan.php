<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

#[Fillable([
    'nomor',
    'status',
    'dealer_id',
    'marketing_id',
    'konsumen_nama',
    'konsumen_nik',
    'konsumen_tgl_lahir',
    'status_perkawinan',
    'data_pasangan',
    'merk_kendaraan',
    'model_kendaraan',
    'tipe_kendaraan',
    'warna_kendaraan',
    'harga_kendaraan',
    'asuransi',
    'down_payment',
    'lama_kredit',
    'angsuran',
    'approved_by',
    'approved_at',
    'catatan_approval',
    'disbursed_by',
    'disbursed_at',
])]
class Pengajuan extends Model
{
    use SoftDeletes;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_SUBMITTED = 'submitted';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_PRINTED = 'printed';

    public const STATUS_SIGNED = 'signed';

    public const STATUS_DISBURSED = 'disbursed';

    public const STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_SUBMITTED,
        self::STATUS_APPROVED,
        self::STATUS_REJECTED,
        self::STATUS_PRINTED,
        self::STATUS_SIGNED,
        self::STATUS_DISBURSED,
    ];

    protected static function booted(): void
    {
        static::creating(function (Pengajuan $pengajuan): void {
            if (blank($pengajuan->public_id)) {
                $pengajuan->public_id = static::generatePublicId();
            }
        });
    }

    public static function generatePublicId(): string
    {
        do {
            $publicId = Str::lower(Str::random(6));
        } while (static::withTrashed()->where('public_id', $publicId)->exists());

        return $publicId;
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    protected function casts(): array
    {
        return [
            'konsumen_tgl_lahir' => 'date',
            'harga_kendaraan' => 'decimal:2',
            'down_payment' => 'decimal:2',
            'angsuran' => 'decimal:2',
            'lama_kredit' => 'integer',
            'approved_at' => 'datetime',
            'disbursed_at' => 'datetime',
        ];
    }

    public function dealer(): BelongsTo
    {
        return $this->belongsTo(Dealer::class);
    }

    public function marketing(): BelongsTo
    {
        return $this->belongsTo(User::class, 'marketing_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function disburser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'disbursed_by');
    }

    public function dokumens()
    {
        return $this->hasMany(DokumenPengajuan::class);
    }

    public static function statusLabel(string $status): string
    {
        return match ($status) {
            self::STATUS_DRAFT => 'Draft',
            self::STATUS_SUBMITTED => 'Menunggu Approval',
            self::STATUS_APPROVED => 'Disetujui',
            self::STATUS_REJECTED => 'Ditolak',
            self::STATUS_PRINTED => 'Dokumen Dicetak',
            self::STATUS_SIGNED => 'Sudah TTD',
            self::STATUS_DISBURSED => 'Dana Dicairkan',
            default => $status,
        };
    }

    public function hasDokumen(string $tipe): bool
    {
        return $this->dokumens->contains('tipe', $tipe);
    }

    public function canEdit(): bool
    {
        return in_array($this->status, [self::STATUS_DRAFT, self::STATUS_REJECTED], true);
    }
}
