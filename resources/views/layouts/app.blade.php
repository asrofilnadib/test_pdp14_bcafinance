<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') — JKL Finance</title>
    @fonts
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/selectize.js/0.15.2/css/selectize.default.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/photoswipe@5.4.4/dist/photoswipe.css">
    @viteReactRefresh
    @vite(['resources/css/app.css', 'resources/js/app.jsx'])
</head>
<body class="min-h-screen bg-[#eef2f7]">
    <div class="flex min-h-screen">
        <aside id="sidebar" class="fixed inset-y-0 left-0 z-40 flex w-64 -translate-x-full flex-col bg-navy-950 text-slate-200 transition-transform lg:static lg:translate-x-0">
            <div class="flex items-center gap-3 border-b border-white/10 px-5 py-5">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-gold-500 font-bold text-navy-950">JK</div>
                <div>
                    <p class="text-sm font-semibold tracking-wide text-white">JKL Finance</p>
                    <p class="text-xs text-slate-400">Kredit Kendaraan</p>
                </div>
            </div>
            <nav class="flex-1 space-y-1 px-3 py-4">
                <a href="/dashboard" class="sidebar-link {{ request()->is('dashboard') ? 'active' : '' }} flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm hover:bg-white/5">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l9-9 9 9M5 10v10h14V10"/></svg>
                    Dashboard
                </a>
                <a href="/pengajuan" class="sidebar-link {{ request()->is('pengajuan*') ? 'active' : '' }} flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm hover:bg-white/5">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6M7 4h10a2 2 0 012 2v14l-7-3-7 3V6a2 2 0 012-2z"/></svg>
                    Pengajuan Kredit
                </a>
            </nav>
            <div class="border-t border-white/10 px-4 py-4">
                <p class="truncate text-sm font-medium text-white">{{ auth()->user()->name }}</p>
                <p class="text-xs text-slate-400">{{ \App\Models\User::roleLabel(auth()->user()->role) }}</p>
            </div>
        </aside>
        <div class="flex min-h-screen flex-1 flex-col">
            <header class="sticky top-0 z-30 flex items-center justify-between border-b border-slate-200 bg-white px-4 py-3 lg:px-6">
                <div class="flex items-center gap-3">
                    <button type="button" id="sidebar-toggle" class="rounded-lg border border-slate-200 p-2 lg:hidden">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                    </button>
                    <div>
                        <h1 class="text-base font-semibold text-navy-900">@yield('title', 'Dashboard')</h1>
                        <p class="text-xs text-slate-500">@yield('subtitle', 'PT. JKL — proses kredit digital')</p>
                    </div>
                </div>
                <button type="button" id="btn-logout" class="rounded-lg border border-slate-200 px-3 py-1.5 text-sm text-slate-600 hover:bg-slate-50">Keluar</button>
            </header>
            <main class="flex-1 p-4 lg:p-6">
                @yield('content')
            </main>
        </div>
    </div>
    <div id="sidebar-backdrop" class="fixed inset-0 z-30 hidden bg-navy-950/50 lg:hidden"></div>
    <div id="page-loader"><div class="spinner"></div></div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/selectize.js/0.15.2/js/selectize.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/photoswipe@5.4.4/dist/umd/photoswipe.umd.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/photoswipe@5.4.4/dist/umd/photoswipe-lightbox.umd.min.js"></script>
    <script src="https://code.highcharts.com/highcharts.js"></script>
    <script src="https://code.highcharts.com/highcharts-more.js"></script>
    <script src="https://code.highcharts.com/modules/funnel.js"></script>
    <script src="https://code.highcharts.com/modules/exporting.js"></script>
    <script src="https://code.highcharts.com/modules/export-data.js"></script>
    <script src="https://code.highcharts.com/modules/accessibility.js"></script>
    @php
        $authUser = [
            'id' => auth()->id(),
            'name' => auth()->user()->name,
            'role' => auth()->user()->role,
            'dealer_id' => auth()->user()->dealer_id,
        ];
    @endphp
    <script>
        window.authUser = @json($authUser);
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        });
        toastr.options = { closeButton: true, progressBar: true, positionClass: 'toast-top-right', timeOut: 3500 };
        window.ajaxError = function (xhr) {
            const res = xhr.responseJSON || {};
            if (res.errors) {
                const first = Object.values(res.errors)[0];
                toastr.error(Array.isArray(first) ? first[0] : first);
                return;
            }
            toastr.error(res.message || 'Terjadi kesalahan.');
        };
        window.showLoader = function () { $('#page-loader').addClass('is-open'); };
        window.hideLoader = function () { $('#page-loader').removeClass('is-open'); };
        $('#sidebar-toggle').on('click', function () {
            $('#sidebar').toggleClass('-translate-x-full');
            $('#sidebar-backdrop').toggleClass('hidden');
        });
        $('#sidebar-backdrop').on('click', function () {
            $('#sidebar').addClass('-translate-x-full');
            $(this).addClass('hidden');
        });
        $('#btn-logout').on('click', function () {
            Swal.fire({
                title: 'Keluar dari sistem?',
                text: 'Sesi Anda akan diakhiri.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#102844',
                cancelButtonColor: '#94a3b8',
                confirmButtonText: 'Ya, keluar',
                cancelButtonText: 'Batal'
            }).then(function (result) {
                if (!result.isConfirmed) return;
                showLoader();
                $.ajax({
                    url: '/logout',
                    type: 'POST',
                    success: function () { window.location.href = '/login'; },
                    error: function () { hideLoader(); toastr.error('Gagal keluar. Coba lagi.'); }
                });
            });
        });
    </script>
    @stack('scripts')
</body>
</html>
