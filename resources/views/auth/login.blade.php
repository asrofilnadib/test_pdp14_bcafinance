@extends('layouts.guest')

@section('title', 'Masuk')

@section('content')
<div class="grid min-h-screen lg:grid-cols-2">
    <div class="relative hidden overflow-hidden bg-navy-900 lg:flex lg:flex-col lg:justify-between p-12">
        <div class="absolute -right-16 -top-16 h-64 w-64 rounded-full bg-gold-500/20 blur-3xl"></div>
        <div class="absolute -bottom-10 -left-10 h-72 w-72 rounded-full bg-navy-700/80 blur-3xl"></div>
        <div class="relative">
            <div class="flex items-center gap-3">
                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-gold-500 text-lg font-bold text-navy-950">JK</div>
                <div>
                    <p class="text-lg font-semibold text-white">JKL Finance</p>
                    <p class="text-sm text-slate-300">PT. JKL — Kredit Kendaraan Bermotor</p>
                </div>
            </div>
        </div>
        <div class="relative max-w-md space-y-4">
            <h2 class="text-3xl font-semibold leading-tight text-white">Pengajuan kredit tanpa antre berkas fisik.</h2>
            <p class="text-slate-300">Input data, unggah dokumen, approval atasan, cetak kontrak/PO, hingga pencairan — semua dalam satu alur digital.</p>
        </div>
        <p class="relative text-xs text-slate-500">Portal internal digitalisasi proses kredit</p>
    </div>
    <div class="flex items-center justify-center p-6">
        <div class="w-full max-w-md rounded-2xl bg-white p-8 shadow-xl">
            <h1 class="text-2xl font-semibold text-navy-900">Masuk</h1>
            <p class="mt-1 text-sm text-slate-500">Gunakan akun sesuai peran Anda.</p>
            <form id="login-form" class="mt-6 space-y-4">
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Email</label>
                    <input type="email" name="email" required class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm outline-none focus:border-navy-700" placeholder="marketing@jkl.test">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Password</label>
                    <input type="password" name="password" required class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm outline-none focus:border-navy-700" placeholder="password">
                </div>
                <button type="submit" class="w-full rounded-xl bg-navy-900 py-2.5 text-sm font-semibold text-white hover:bg-navy-800">Masuk</button>
            </form>
            <div class="mt-6 rounded-xl bg-slate-50 p-4 text-xs text-slate-600">
                <p class="font-semibold text-navy-900">Akun demo (password: <code>password</code>)</p>
                <ul class="mt-2 space-y-1">
                    <li>dealer@jkl.test — Dealer</li>
                    <li>marketing@jkl.test — Marketing</li>
                    <li>atasan@jkl.test — Atasan Marketing</li>
                    <li>admin@jkl.test — Admin Backoffice</li>
                    <li>super@jkl.test — Super User</li>
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$('#login-form').on('submit', function (e) {
    e.preventDefault();
    showLoader();
    $.ajax({
        url: '/login',
        type: 'POST',
        data: {
            email: $('[name=email]').val(),
            password: $('[name=password]').val()
        },
        success: function (res) {
            toastr.success(res.message);
            window.location.href = res.redirect || '/dashboard';
        },
        error: function (xhr) {
            hideLoader();
            ajaxError(xhr);
        }
    });
});
</script>
@endpush
