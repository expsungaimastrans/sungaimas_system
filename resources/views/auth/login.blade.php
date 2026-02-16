<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Login - Sungai Mas System</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

  <style>
    body{
      min-height:100vh;
      background: radial-gradient(1200px 600px at 20% 10%, rgba(13,110,253,.10), transparent 60%),
                  radial-gradient(1200px 600px at 80% 20%, rgba(25,135,84,.10), transparent 60%),
                  #f8fafc;
      display:flex;
      align-items:center;
      justify-content:center;
      padding:24px;
      font-family: system-ui, -apple-system, Segoe UI, Roboto, Arial;
    }
    .login-card{
      border: 1px solid rgba(0,0,0,.08);
      border-radius: 18px;
      overflow:hidden;
      box-shadow: 0 12px 30px rgba(0,0,0,.08);
      background:#fff;
      max-width: 980px;
      width: 100%;
    }
    .left-pane{
      padding: 28px;
      background: linear-gradient(135deg, rgba(13,110,253,.12), rgba(25,135,84,.10));
      height:100%;
    }
    .brand-badge{
      display:inline-flex;
      align-items:center;
      gap:10px;
      background: rgba(255,255,255,.7);
      border: 1px solid rgba(0,0,0,.06);
      padding: 10px 14px;
      border-radius: 14px;
    }
    .brand-title{
      font-weight:800;
      letter-spacing:.2px;
      font-size:18px;
      margin:0;
      line-height:1.2;
    }
    .brand-sub{
      font-size:12px;
      color:#6c757d;
      margin:0;
    }
    .right-pane{
      padding: 28px;
    }
    .form-control{
      border-radius: 12px;
      padding: 10px 12px;
    }
    .btn-brand{
      border-radius: 12px;
      padding: 10px 12px;
      font-weight: 700;
    }
    .hint{
      font-size: 12px;
      color:#6c757d;
      line-height:1.35;
    }
    .logo{
      width: 56px;
      height: 56px;
      object-fit: contain;
    }
  </style>
</head>
<body>

<div class="login-card">
  <div class="row g-0">
    <div class="col-lg-5">
      <div class="left-pane">
        <div class="brand-badge mb-3">
          <img src="{{ asset('logo.png') }}" class="logo" alt="Logo Sungai Mas">
          <div>
            <p class="brand-title">Sungai Mas System</p>
            <p class="brand-sub">Expedisi • Manifest • Finance</p>
          </div>
        </div>

        <div class="mt-4">
          <div class="fw-semibold">Akses Role</div>
          <div class="hint mt-2">
            • <b>Owner</b>: Dashboard + semua fitur<br>
            • <b>Admin</b>: Buat nota & manifest (tanpa finance)<br>
            • <b>Finance</b>: Finance saja (tanpa buat nota/manifest)<br>
          </div>
        </div>

        <div class="mt-4 hint">
          Pastikan koneksi stabil.<br>
          Jika lupa password, owner bisa mengganti via database (atau nanti kita buat page manajemen user).
        </div>
      </div>
    </div>

    <div class="col-lg-7">
      <div class="right-pane">
        <div class="d-flex justify-content-between align-items-start">
          <div>
            <div class="h4 mb-1 fw-bold">Login</div>
            <div class="text-muted">Masuk menggunakan username & password</div>
          </div>
        </div>

        @if($errors->any())
          <div class="alert alert-danger mt-3 py-2">
            {{ $errors->first() }}
          </div>
        @endif

        <form method="POST" action="{{ route('login.post') }}" class="mt-3">
          @csrf

          <div class="mb-3">
            <label class="form-label fw-semibold">Username</label>
            <input type="text" name="username" class="form-control" value="{{ old('username') }}" required autofocus>
          </div>

          <div class="mb-2">
            <label class="form-label fw-semibold">Password</label>
            <input type="password" name="password" class="form-control" required>
          </div>

          <div class="d-flex justify-content-between align-items-center mt-3">
            <div class="form-check">
              <input class="form-check-input" type="checkbox" name="remember" id="remember">
              <label class="form-check-label" for="remember">Remember</label>
            </div>
          </div>

          <button class="btn btn-primary btn-brand w-100 mt-3">Masuk</button>
        </form>

        <div class="hint mt-3">
          © {{ date('Y') }} Sungai Mas Trans
        </div>
      </div>
    </div>
  </div>
</div>

</body>
</html>
