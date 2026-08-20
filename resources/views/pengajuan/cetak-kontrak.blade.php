<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Kontrak Kredit {{ $pengajuan->nomor }}</title>
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
        <div class="flex items-start justify-between border-b-2 border-navy-900 pb-4">
            <div>
                <p class="text-xs tracking-[0.2em] text-gold-600">PT. JKL</p>
                <h1 class="text-2xl font-semibold text-navy-900">Perjanjian Pembiayaan Kendaraan</h1>
                <p class="text-sm text-slate-500">Dokumen Kontrak Kredit</p>
            </div>
            <div class="text-right text-sm">
                <p class="font-semibold">{{ $pengajuan->nomor }}</p>
                <p>{{ now()->translatedFormat('d F Y') }}</p>
            </div>
        </div>
        <div class="mt-6 space-y-4 text-sm leading-6">
            <p>Yang bertanda tangan di bawah ini:</p>
            <p><strong>Pihak Pembiayaan:</strong> PT. JKL, selanjutnya disebut Perusahaan.</p>
            <p><strong>Pihak Konsumen:</strong> {{ $pengajuan->konsumen_nama }}, NIK {{ $pengajuan->konsumen_nik }}, selanjutnya disebut Debitur.</p>
            <h2 class="mt-6 font-semibold text-navy-900">Objek Pembiayaan</h2>
            <table class="w-full text-sm">
                <tr><td class="w-40 py-1">Dealer</td><td>{{ $pengajuan->dealer?->nama }}</td></tr>
                <tr><td class="py-1">Kendaraan</td><td>{{ $pengajuan->merk_kendaraan }} {{ $pengajuan->model_kendaraan }} {{ $pengajuan->tipe_kendaraan }}</td></tr>
                <tr><td class="py-1">Warna</td><td>{{ $pengajuan->warna_kendaraan }}</td></tr>
                <tr><td class="py-1">Harga</td><td>Rp {{ number_format($pengajuan->harga_kendaraan, 0, ',', '.') }}</td></tr>
                <tr><td class="py-1">Down Payment</td><td>Rp {{ number_format($pengajuan->down_payment, 0, ',', '.') }}</td></tr>
                <tr><td class="py-1">Tenor</td><td>{{ $pengajuan->lama_kredit }} bulan</td></tr>
                <tr><td class="py-1">Angsuran</td><td>Rp {{ number_format($pengajuan->angsuran, 0, ',', '.') }} / bulan</td></tr>
                <tr><td class="py-1">Asuransi</td><td>{{ $pengajuan->asuransi }}</td></tr>
            </table>
            <p class="mt-4">Debitur setuju membayar angsuran tepat waktu sesuai tenor di atas. Dokumen ini dicetak dari sistem digital JKL Finance dan sah setelah ditandatangani para pihak.</p>
        </div>
        <div class="mt-16 grid grid-cols-3 gap-6 text-center text-sm">
            <div>
                <p>Konsumen</p>
                <div class="mt-16 border-t pt-2">{{ $pengajuan->konsumen_nama }}</div>
            </div>
            <div>
                <p>Marketing</p>
                <div class="mt-16 border-t pt-2">{{ $pengajuan->marketing?->name ?? '________________' }}</div>
            </div>
            <div>
                <p>Perusahaan</p>
                <div class="mt-16 border-t pt-2">PT. JKL</div>
            </div>
        </div>
    </div>
</body>
</html>
