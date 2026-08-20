<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'role', 'dealer_id'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const ROLE_DEALER = 'dealer';
    public const ROLE_MARKETING = 'marketing';
    public const ROLE_ATASAN = 'atasan_marketing';
    public const ROLE_ADMIN = 'admin_backoffice';
    public const ROLE_SUPER = 'super_user';

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function dealer(): BelongsTo
    {
        return $this->belongsTo(Dealer::class);
    }

    public function pengajuans(): HasMany
    {
        return $this->hasMany(Pengajuan::class, 'marketing_id');
    }

    public function isDealer(): bool
    {
        return $this->role === self::ROLE_DEALER;
    }

    public function isMarketing(): bool
    {
        return $this->role === self::ROLE_MARKETING;
    }

    public function isAtasan(): bool
    {
        return $this->role === self::ROLE_ATASAN;
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isSuperUser(): bool
    {
        return $this->role === self::ROLE_SUPER;
    }

    public function canCreatePengajuan(): bool
    {
        return in_array($this->role, [self::ROLE_DEALER, self::ROLE_MARKETING, self::ROLE_SUPER], true);
    }

    public function canApprove(): bool
    {
        return $this->isAtasan() || $this->isSuperUser();
    }

    public function canPrintOrDisburse(): bool
    {
        return $this->isAdmin() || $this->isSuperUser();
    }

    public static function roleLabel(?string $role): string
    {
        return match ($role) {
            self::ROLE_DEALER => 'Dealer',
            self::ROLE_MARKETING => 'Marketing',
            self::ROLE_ATASAN => 'Atasan Marketing',
            self::ROLE_ADMIN => 'Admin Backoffice',
            self::ROLE_SUPER => 'Super User',
            default => $role ?? '-',
        };
    }
}
