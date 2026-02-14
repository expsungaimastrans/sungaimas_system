<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Expedisi Sungai Mas')</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        :root{
            --brand:#198754;
            --bg:#f4f6f8;
            --card-radius:14px;
            --muted:#6c757d;
        }
        body{ background:var(--bg); }

        .app-container{ max-width: 1100px; }

        .card{
            border-radius: var(--card-radius);
            border: 1px solid rgba(0,0,0,.06);
        }

        .page-title{
            font-weight:800;
            letter-spacing:.2px;
        }

        .section-title{
            font-weight:800;
            color:var(--brand);
            margin-bottom:10px;
        }

        .btn-brand{
            background:var(--brand);
            border-color:var(--brand);
            color:#fff;
        }
        .btn-brand:hover{ filter:brightness(.95); color:#fff; }

        .form-control, .form-select{
            border-radius:12px;
        }

        .table thead th{
            background: rgba(25,135,84,.12);
            border-bottom: 1px solid rgba(0,0,0,.08);
            text-align:center;
            vertical-align:middle;
            padding: 10px 8px;
            font-weight: 800;
        }
        .table td{
            vertical-align:middle;
        }

        /* agar konten tidak mepet pinggir */
        .content-card-body{ padding: 22px; }

        @stack('styles')
    </style>
</head>
<body>

@include('partials.navbar')

<main class="container app-container py-4">
    @yield('content')
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
@stack('scripts')
</body>
</html>
