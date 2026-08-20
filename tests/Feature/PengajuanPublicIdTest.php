<?php

use App\Models\Dealer;
use App\Models\Pengajuan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function makePengajuan(?User $marketing = null): Pengajuan
{
    $dealer = Dealer::query()->create([
        'nama' => 'Honda Maju Motor',
        'alamat' => 'Jl. Test',
        'telepon' => '0215550101',
    ]);

    $marketing ??= User::factory()->create([
        'role' => User::ROLE_MARKETING,
    ]);

    return Pengajuan::query()->create([
        'nomor' => 'JKL-20260820-0008',
        'status' => Pengajuan::STATUS_DRAFT,
        'dealer_id' => $dealer->id,
        'marketing_id' => $marketing->id,
        'konsumen_nama' => 'Citra Ayu',
        'konsumen_nik' => '3174010801930008',
    ]);
}

test('pengajuan gets a 6 character public_id when created', function () {
    $pengajuan = makePengajuan();

    expect($pengajuan->public_id)
        ->toHaveLength(6)
        ->toMatch('/^[a-z0-9]{6}$/');
});

test('detail page uses public_id instead of numeric id', function () {
    $user = User::factory()->create(['role' => User::ROLE_SUPER]);
    $pengajuan = makePengajuan($user);

    $this->actingAs($user)
        ->get('/pengajuan/'.$pengajuan->id)
        ->assertNotFound();

    $this->actingAs($user)
        ->get('/pengajuan/'.$pengajuan->public_id)
        ->assertOk()
        ->assertSee($pengajuan->public_id, false);
});

test('json endpoint is resolved by public_id', function () {
    $user = User::factory()->create(['role' => User::ROLE_SUPER]);
    $pengajuan = makePengajuan($user);

    $this->actingAs($user)
        ->getJson('/pengajuan/'.$pengajuan->public_id.'/json')
        ->assertOk()
        ->assertJsonPath('public_id', $pengajuan->public_id)
        ->assertJsonPath('konsumen_nama', 'Citra Ayu');
});
