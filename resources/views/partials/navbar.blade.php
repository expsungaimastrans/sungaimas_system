<nav class="navbar navbar-expand-lg bg-white border-bottom">
  <div class="container">
    <a class="navbar-brand fw-bold"
       href="{{ auth()->check() && strtolower(auth()->user()->role) === 'owner' ? '/dashboard' : '/shipments' }}">
      <span class="text-success">Expedisi</span> Sungai Mas
    </a>

    <div class="collapse navbar-collapse">
      <ul class="navbar-nav ms-auto gap-2">

        @auth
          @php $role = strtolower(auth()->user()->role ?? ''); @endphp

          @if($role === 'owner')
            <li class="nav-item"><a class="nav-link" href="/dashboard">Dashboard</a></li>
          @endif

          <li class="nav-item"><a class="nav-link" href="/shipments">Nota</a></li>

          @if(in_array($role, ['owner', 'admin']))
            <li class="nav-item"><a class="nav-link" href="/shipments/create">Buat Nota</a></li>
          @endif

          <li class="nav-item"><a class="nav-link" href="/manifests">Manifest</a></li>

          @if(in_array($role, ['owner', 'admin']))
            <li class="nav-item"><a class="nav-link" href="/manifests/create">Buat Manifest</a></li>
          @endif

          @if(in_array($role, ['owner', 'finance']))
            <li class="nav-item"><a class="nav-link" href="/finance">Finance</a></li>
            <li class="nav-item"><a class="nav-link" href="{{ route('finance.invoices.list') }}">Tagihan</a></li>
          @endif

          @if(in_array($role, ['owner', 'admin']))
            <li class="nav-item"><a class="nav-link" href="{{ route('customers.index') }}">Customer</a></li>
          @endif

          <div class="d-flex align-items-center gap-2">
            <span class="text-muted small">
              {{ auth()->user()->username }} ({{ auth()->user()->role }})
            </span>
            <form method="POST" action="{{ route('logout') }}">
              @csrf
              <button class="btn btn-sm btn-outline-secondary">Logout</button>
            </form>
          </div>

        @else
          <a href="{{ route('login') }}" class="btn btn-sm btn-outline-secondary">Login</a>
        @endauth

      </ul>
    </div>
  </div>
</nav>