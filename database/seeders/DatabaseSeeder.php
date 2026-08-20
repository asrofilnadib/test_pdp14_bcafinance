<?php

namespace Database\Seeders;

use App\Models\Dealer;
use App\Models\Pengajuan;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $dealerHonda = Dealer::query()->updateOrCreate(
            ['nama' => 'Honda Maju Motor'],
            [
                'alamat' => 'Jl. Gatot Subroto No. 12, Jakarta Selatan',
                'telepon' => '021-5550101',
            ]
        );

        $dealerToyota = Dealer::query()->updateOrCreate(
            ['nama' => 'Toyota Sumber Rejeki'],
            [
                'alamat' => 'Jl. Ahmad Yani No. 88, Bekasi',
                'telepon' => '021-5550202',
            ]
        );

        $password = 'password';

        $dealerUser = User::query()->updateOrCreate(
            ['email' => 'dealer@jkl.test'],
            [
                'name' => 'Budi Dealer',
                'password' => $password,
                'role' => User::ROLE_DEALER,
                'dealer_id' => $dealerHonda->id,
                'email_verified_at' => now(),
            ]
        );

        $marketing = User::query()->updateOrCreate(
            ['email' => 'marketing@jkl.test'],
            [
                'name' => 'Sari Marketing',
                'password' => $password,
                'role' => User::ROLE_MARKETING,
                'email_verified_at' => now(),
            ]
        );

        $atasan = User::query()->updateOrCreate(
            ['email' => 'atasan@jkl.test'],
            [
                'name' => 'Rudi Atasan',
                'password' => $password,
                'role' => User::ROLE_ATASAN,
                'email_verified_at' => now(),
            ]
        );

        $admin = User::query()->updateOrCreate(
            ['email' => 'admin@jkl.test'],
            [
                'name' => 'Nina Admin',
                'password' => $password,
                'role' => User::ROLE_ADMIN,
                'email_verified_at' => now(),
            ]
        );

        $samples = [
            ['status' => Pengajuan::STATUS_DRAFT, 'nama' => 'Andi Pratama', 'nik' => '3174010101990001', 'dealer' => $dealerHonda, 'marketing' => $marketing],
            ['status' => Pengajuan::STATUS_SUBMITTED, 'nama' => 'Dewi Lestari', 'nik' => '3174010202000002', 'dealer' => $dealerHonda, 'marketing' => $marketing],
            ['status' => Pengajuan::STATUS_APPROVED, 'nama' => 'Fajar Nugroho', 'nik' => '3174010301980003', 'dealer' => $dealerToyota, 'marketing' => $marketing],
            ['status' => Pengajuan::STATUS_REJECTED, 'nama' => 'Lina Kusuma', 'nik' => '3174010401970004', 'dealer' => $dealerHonda, 'marketing' => $marketing],
            ['status' => Pengajuan::STATUS_PRINTED, 'nama' => 'Agus Salim', 'nik' => '3174010501960005', 'dealer' => $dealerToyota, 'marketing' => $marketing],
            ['status' => Pengajuan::STATUS_SIGNED, 'nama' => 'Maya Sari', 'nik' => '3174010601950006', 'dealer' => $dealerHonda, 'marketing' => $marketing],
            ['status' => Pengajuan::STATUS_DISBURSED, 'nama' => 'Raka Putra', 'nik' => '3174010701940007', 'dealer' => $dealerToyota, 'marketing' => $marketing],
            ['status' => Pengajuan::STATUS_DRAFT, 'nama' => 'Citra Ayu', 'nik' => '3174010801930008', 'dealer' => $dealerHonda, 'marketing' => $dealerUser],
        ];

        foreach ($samples as $i => $row) {
            $harga = 180000000 + ($i * 15000000);
            $dp = 30000000 + ($i * 1000000);
            $tenor = 12 + (($i % 4) * 12);
            $pokok = $harga - $dp;

            Pengajuan::query()->updateOrCreate(
                ['nomor' => 'JKL-'.now()->format('Ymd').'-'.str_pad((string) ($i + 1), 4, '0', STR_PAD_LEFT)],
                [
                    'status' => $row['status'],
                    'dealer_id' => $row['dealer']->id,
                    'marketing_id' => $row['marketing']->id,
                    'konsumen_nama' => $row['nama'],
                    'konsumen_nik' => $row['nik'],
                    'konsumen_tgl_lahir' => now()->subYears(28 + $i)->subDays($i * 12),
                    'status_perkawinan' => $i % 2 === 0 ? 'menikah' : 'belum_menikah',
                    'data_pasangan' => $i % 2 === 0 ? 'Pasangan '.$row['nama'] : null,
                    'merk_kendaraan' => $row['dealer']->id === $dealerHonda->id ? 'Honda' : 'Toyota',
                    'model_kendaraan' => $row['dealer']->id === $dealerHonda->id ? 'HR-V' : 'Innova',
                    'tipe_kendaraan' => '1.5 SE CVT',
                    'warna_kendaraan' => $i % 2 === 0 ? 'Putih' : 'Hitam',
                    'harga_kendaraan' => $harga,
                    'asuransi' => $i % 2 === 0 ? 'All Risk' : 'TLO',
                    'down_payment' => $dp,
                    'lama_kredit' => $tenor,
                    'angsuran' => round($pokok / $tenor, 2),
                    'approved_by' => in_array($row['status'], [Pengajuan::STATUS_APPROVED, Pengajuan::STATUS_PRINTED, Pengajuan::STATUS_SIGNED, Pengajuan::STATUS_DISBURSED, Pengajuan::STATUS_REJECTED], true) ? $atasan->id : null,
                    'approved_at' => in_array($row['status'], [Pengajuan::STATUS_APPROVED, Pengajuan::STATUS_PRINTED, Pengajuan::STATUS_SIGNED, Pengajuan::STATUS_DISBURSED], true) ? now()->subDays(3) : null,
                    'catatan_approval' => $row['status'] === Pengajuan::STATUS_REJECTED ? 'Data penghasilan belum lengkap.' : null,
                    'disbursed_by' => $row['status'] === Pengajuan::STATUS_DISBURSED ? $admin->id : null,
                    'disbursed_at' => $row['status'] === Pengajuan::STATUS_DISBURSED ? now()->subDay() : null,
                ]);
        }
    }
}
