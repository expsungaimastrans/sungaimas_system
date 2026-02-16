<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title','Sungai Mas System')</title>

    {{-- Bootstrap --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    {{-- Font --}}
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    {{-- App CSS (jika kamu sudah punya public css sendiri) --}}
    <style>
        body{ font-family: Figtree, system-ui, -apple-system, Segoe UI, Roboto, sans-serif; }

        /* style dasar biar seragam */
        .page-title{ font-weight: 700; letter-spacing: .2px; }
        .section-title{ font-weight: 700; margin-bottom: .5rem; }
        .content-card-body{ padding: 18px; }

        /* btn brand */
        .btn-brand{
            background: #0d6efd;
            border-color: #0d6efd;
            color: #fff;
        }
        .btn-brand:hover{ opacity: .92; color:#fff; }

        .app-container{ max-width: 1200px; }
    </style>

    @stack('styles')
</head>
<body>

{{-- Navbar --}}
@include('partials.navbar')

<main class="container app-container py-4">
    @yield('content')
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
@stack('scripts')
</body>
</html>
