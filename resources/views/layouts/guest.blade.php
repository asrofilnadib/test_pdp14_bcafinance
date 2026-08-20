<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Masuk') — JKL Finance</title>
    @fonts
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
    @vite(['resources/css/app.css', 'resources/js/app.jsx'])
</head>
<body class="min-h-screen bg-navy-950">
    @yield('content')
    <div id="page-loader"><div class="spinner"></div></div>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    <script>
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
    </script>
    @stack('scripts')
</body>
</html>
