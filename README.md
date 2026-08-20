# JKL Finance — Digitalisasi Pengajuan Kredit Kendaraan

Aplikasi web untuk **PT. JKL** (soal coding PDP BCA Finance, nomor **2.A**). Proses penerimaan pengajuan kredit kendaraan yang tadinya manual (fotokopi, scan, print, routing kertas) dipindah ke sistem: input data, unggah dokumen, approval atasan, cetak kontrak/PO, unggah berkas TTD, sampai pencairan dana.

## Soal 1 — Proses yang didigitalisasi

Hampir seluruh langkah 1–9 bisa diimprove:

| Langkah lama | Digitalisasi |
|---|---|
| Tukar dokumen fisik (KTP, SPK, bukti bayar, KK) | Upload ke sistem |
| Form aplikasi kertas | Form web + simpan database |
| Approval atasan via kertas | Approve / reject di aplikasi |
| Print kontrak & PO manual | Generate dokumen digital lalu cetak |
| TTD keliling fisik | Tracking status + upload berkas sudah TTD |
| Pencairan dana tidak tercatat | Status pencairan + petugas + waktu |

Yang tetap di luar sistem: deal awal di dealer dan basah-tinta TTD di lapangan. Sistem hanya merekam hasilnya.

```mermaid
flowchart TB
    subgraph eksternal [Eksternal - manual lama]
        S1["1. Deal pembelian kendaraan"]
        S1D["KTP / SPK / Bukti Bayar fisik"]
        S2["2. Sales Dealer kirim info kredit"]
        S1 --> S1D --> S2
    end

    subgraph internal [Internal - manual lama]
        S3["3. Marketing hubungi konsumen collect data"]
        S4["4. Submit form kertas + berkas fisik"]
        S5["5. Atasan Marketing approval kertas"]
        S6["6. Admin print Kontrak dan PO"]
        S7["7. TTD keliling Konsumen Marketing Dealer"]
        S8["8. Kumpulkan berkas sudah TTD"]
        S9["9. Pencairan dana manual"]
        S2 --> S3 --> S4 --> S5 --> S6 --> S7 --> S8 --> S9
    end

    subgraph improve [Yang di-digitalisasi]
        D14["Upload dokumen + form pengajuan web"]
        D5["Approval digital Atasan Marketing"]
        D6["Generate Kontrak dan PO digital"]
        D78["Upload berkas TTD + status tracking"]
        D9["Pencairan dana tercatat di sistem"]
    end

    S1D -.-> D14
    S3 -.-> D14
    S4 -.-> D14
    S5 -.-> D5
    S6 -.-> D6
    S7 -.-> D78
    S8 -.-> D78
    S9 -.-> D9
```

## Soal 2 — Alur setelah digitalisasi

Peran: **Dealer**, **Marketing**, **Atasan Marketing**, **Admin Backoffice**. Konsumen tidak login.

Status: `draft` → `submitted` → `approved` / `rejected` → `printed` → `signed` → `disbursed`.

```mermaid
flowchart TB
    Login["Login sesuai role"]
    Input["Marketing atau Dealer input pengajuan"]
    UploadAwal["Upload KTP SPK BuktiBayar KK"]
    Draft["Status draft"]
    Submit["Marketing submit pengajuan"]
    Review["Atasan Marketing review"]
    Reject["Status rejected + catatan"]
    Approve["Status approved"]
    PrintDoc["Admin generate Kontrak dan PO"]
    Printed["Status printed"]
    TTD["TTD di lapangan"]
    UploadTTD["Upload dokumen sudah TTD"]
    Signed["Status signed"]
    Cair["Admin proses pencairan"]
    Done["Status disbursed"]

    Login --> Input --> UploadAwal --> Draft --> Submit --> Review
    Review -->|tolak| Reject
    Review -->|setuju| Approve --> PrintDoc --> Printed --> TTD --> UploadTTD --> Signed --> Cair --> Done
```

```mermaid
erDiagram
    users ||--o{ pengajuans : "marketing / approver / admin"
    dealers ||--o{ pengajuans : punya
    dealers ||--o{ users : "user dealer"
    pengajuans ||--o{ dokumen_pengajuans : punya

    users {
        bigint id PK
        string name
        string email
        string role
        bigint dealer_id FK
    }

    dealers {
        bigint id PK
        string nama
        string alamat
        string telepon
    }

    pengajuans {
        bigint id PK
        string nomor
        string status
        bigint dealer_id FK
        bigint marketing_id FK
        string konsumen_nama
        string konsumen_nik
        decimal harga_kendaraan
        decimal down_payment
        int lama_kredit
        decimal angsuran
    }

    dokumen_pengajuans {
        bigint id PK
        bigint pengajuan_id FK
        string tipe
        string path
    }
```

## Stack

- Laravel 13 (PHP 8.3)
- React + Vite + Tailwind CSS 4
- shadcn/ui (Dialog, AlertDialog, Button, Card, Badge)
- MySQL 8
- DataTables server-side, jQuery AJAX
- Highcharts (dashboard)
- SweetAlert2, Toastr, PhotoSwipe (CDN)

Create/update pengajuan memakai **modal**, hapus memakai **konfirmasi modal**. List pengajuan paging/search/sort dikerjakan di server.

## Hak akses

| Peran | Yang bisa dilakukan |
|---|---|
| Dealer / Marketing | Buat pengajuan, ubah draft milik sendiri, unggah dokumen awal, unggah TTD |
| Atasan Marketing | Review pengajuan `submitted`, approve / reject |
| Admin Backoffice | Cetak kontrak & PO setelah approved, pencairan setelah signed |
| Super User | Semua akses di atas, melihat seluruh pengajuan |

## Cara jalanin (Docker)

Paling gampang, tidak perlu install PHP/Node/MySQL di host:

```bash
docker compose up --build
```

Buka [http://localhost:8000](http://localhost:8000).

Container `app` otomatis `migrate` + `seed` (bukan `migrate:fresh`). MySQL Docker di port **3307** supaya tidak tabrakan dengan MySQL lokal (3306).

```bash
docker compose down        # stop, data tetap
docker compose down -v     # stop + hapus volume MySQL
```

## Cara jalanin (lokal)

Kebutuhan: PHP 8.3, Composer, Node.js, MySQL.

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
```

Isi koneksi MySQL di `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=pdpbcaf
DB_USERNAME=root
DB_PASSWORD=
```

Buat database `pdpbcaf`, lalu:

```bash
php artisan migrate
php artisan db:seed
php artisan serve
```

Terminal kedua:

```bash
npm run dev
```

Buka URL yang muncul (biasanya `http://localhost:8000`).

Jangan pakai `migrate:fresh` / `migrate:refresh`.

## Akun demo

Password semua akun: `password`

| Email | Peran |
|---|---|
| `dealer@jkl.test` | Dealer |
| `marketing@jkl.test` | Marketing |
| `atasan@jkl.test` | Atasan Marketing |
| `admin@jkl.test` | Admin Backoffice |
| `super@jkl.test` | Super User |

## Alur uji singkat

1. Login **marketing** → Pengajuan Baru (modal) → isi data + unggah KTP/SPK/bukti bayar/KK → Kirim Pengajuan.
2. Login **atasan** → buka detail → Setujui / Tolak.
3. Login **admin** → Cetak Kontrak & PO.
4. Login **marketing** lagi → unggah kontrak & PO yang sudah TTD.
5. Login **admin** → Pencairan Dana.

Dashboard menampilkan kartu status plus chart Highcharts (kolom, pie, funnel, tren 6 bulan, bar per dealer).
