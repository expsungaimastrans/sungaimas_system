<nav class="navbar navbar-expand-lg bg-white border-bottom">
    <div class="container">
      <a class="navbar-brand fw-bold" href="{{ auth()->check() && auth()->user()->role==='owner' ? '/dashboard' : '/shipments' }}">
        <span class="text-success">Expedisi</span> Sungai Mas
      </a>
  
      <div class="collapse navbar-collapse">
        <ul class="navbar-nav ms-auto gap-2">
  
          @auth
            {{-- OWNER: dashboard --}}
            @if(auth()->user()->role === 'owner')
              <li class="nav-item"><a class="nav-link" href="/dashboard">Dashboard</a></li>
            @endif
  
            {{-- Semua boleh lihat daftar nota --}}
            <li class="nav-item"><a class="nav-link" href="/shipments">Nota</a></li>
  
            {{-- Admin + Owner boleh buat nota --}}
            @if(in_array(auth()->user()->role, ['owner','admin']))
              <li class="nav-item"><a class="nav-link" href="/shipments/create">Buat Nota</a></li>
            @endif
  
            {{-- Semua boleh lihat manifest list --}}
            <li class="nav-item"><a class="nav-link" href="/manifests">Manifest</a></li>
  
            {{-- Admin + Owner boleh buat manifest --}}
            @if(in_array(auth()->user()->role, ['owner','admin']))
              <li class="nav-item"><a class="nav-link" href="/manifests/create">Buat Manifest</a></li>
            @endif
  
            {{-- Finance + Owner --}}
            @if(in_array(auth()->user()->role, ['owner','finance']))
              <li class="nav-item"><a class="nav-link" href="/finance">Finance</a></li>
            @endif
  
            <li class="nav-item">
              <form method="POST" action="/logout">
                @csrf
                <button class="btn btn-sm btn-outline-secondary">Logout</button>
              </form>
            </li>
          @endauth
  
        </ul>
      </div>
    </div>
  </nav>
  