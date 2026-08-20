<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Purchase Order {{ $pengajuan->nomor }}</title>
    @vite(['resources/css/app.css'])
    <style>
        @media print { .no-print { display: none !important; } }
        body { background: #fff; }
    </style>
</head>
<body class="bg-white text-slate-800">
    <div class="no-print mx-auto flex max-w-3xl justify-end gap-2 p-4">
        <button onclick="window.print()" class="rounded-lg bg-navy-900 px-4 py-2 text-sm text-white">Cetak</button>
        <button onclick="window.close()" class="rounded-lg border px-4 py-2 text-sm">Tutup</button>
    </div>
    <div class="mx-auto max-w-3xl p-8">
        <div class="flex items-start justify-between border-b-2 border-gold-500 pb-4">
            <div>
                <p class="text-xs tracking-[0.2em] text-navy-700">PT. JKL</p>
                <h1 class="text-2xl font-semibold text-navy-900">Purchase Order</h1>
                <p class="text-sm text-slate-500">Dokumen PO ke Dealer</p>
            </div>
            <div class="text-right text-sm">
                <p class="font-semibold">{{ $pengajuan->nomor }}</p>
                <p>{{ now()->format('d/m/Y') }}</p>
            </div>
        </div>
        <div class="mt-6 text-sm">
            <p>Kepada:</p>
            <p class="text-lg font-semibold text-navy-900">{{ $pengajuan->dealer?->nama }}</p>
            <p>{{ $pengajuan->dealer?->alamat }}</p>
            <p class="mt-4">Mohon disiapkan kendaraan dengan rincian berikut untuk konsumen <strong>{{ $pengajuan->konsumen_nama }}</strong>:</p>
            <table class="mt-4 w-full border text-sm">
                <thead class="bg-navy-900 text-white">
                    <tr>
                        <th class="px-3 py-2 text-left">Merk / Model</th>
                        <th class="px-3 py-2 text-left">Tipe</th>
                        <th class="px-3 py-2 text-left">Warna</th>
                        <th class="px-3 py-2 text-right">Harga</th>
                    </tr>
                </thead>
                <tbody>
                    <tr class="border-t">
                        <td class="px-3 py-2">{{ $pengajuan->merk_kendaraan }} {{ $pengajuan->model_kendaraan }}</td>
                        <td class="px-3 py-2">{{ $pengajuan->tipe_kendaraan }}</td>
                        <td class="px-3 py-2">{{ $pengajuan->warna_kendaraan }}</td>
                        <td class="px-3 py-2 text-right">Rp {{ number_format($pengajuan->harga_kendaraan, 0, ',', '.') }}</td>
                    </tr>
                </tbody>
            </table>
            <p class="mt-4">Down Payment: <strong>Rp {{ number_format($pengajuan->down_payment, 0, ',', '.') }}</strong></p>
        </div>
        <div class="mt-16 grid grid-cols-2 gap-10 text-center text-sm">
            <div>
                <p>Marketing JKL</p>
                <div class="mt-16 border-t pt-2">{{ $pengajuan->marketing?->name ?? '________________' }}</div>
            </div>
            <div>
                <p>Dealer</p>
                <div class="mt-16 border-t pt-2">{{ $pengajuan->dealer?->nama }}</div>
            </div>
        </div>
    </div>
</body>
</html>
