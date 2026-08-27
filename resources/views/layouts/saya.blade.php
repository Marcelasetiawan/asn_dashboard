<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>@yield('title', 'Profil Saya') — Bangkom ASN</title>
<link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">
</head>
<body>

<button class="hamburger-btn" id="hamburgerBtn" aria-label="Buka menu">
  <svg class="ic-open" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="4" y1="7" x2="20" y2="7"/><line x1="4" y1="12" x2="20" y2="12"/><line x1="4" y1="17" x2="20" y2="17"/></svg>
  <svg class="ic-close" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 5 8 12 15 19"/></svg>
</button>
<div class="sidebar-backdrop" id="sidebarBackdrop"></div>

<div class="app">

  <aside class="sidebar" id="sidebar">
    <div class="brand">
      <div class="brand-mark"><img src="{{ asset('images/logo-lms.png') }}" alt="Logo Kabupaten Banyuwangi"></div>
      <div class="brand-text">
        <div class="t1">Bangkom ASN</div>
        <div class="t2">Kab. Banyuwangi</div>
      </div>
    </div>

    <div class="nav-group">
      <a class="nav-item {{ request()->routeIs('saya') ? 'active' : '' }}" href="{{ route('saya') }}">Ringkasan</a>
      <a class="nav-item {{ request()->routeIs('saya.profil') ? 'active' : '' }}" href="{{ route('saya.profil') }}">Profil</a>
      <a class="nav-item {{ request()->routeIs('saya.riwayat') ? 'active' : '' }}" href="{{ route('saya.riwayat') }}">Riwayat Pelatihan</a>
      <a class="nav-item {{ request()->routeIs('saya.pelatihan') ? 'active' : '' }}" href="{{ route('saya.pelatihan') }}">Rekomendasi &amp; Pelatihan Wajib</a>
      <a class="nav-item {{ request()->routeIs('saya.akun') ? 'active' : '' }}" href="{{ route('saya.akun') }}">Pengaturan Akun</a>
    </div>
  </aside>

  <main class="content">
    <div class="topbar">
      <div>
        <h1>@yield('title', 'Ringkasan')</h1>
        <div class="sub">@yield('subtitle', '')</div>
      </div>
      <form method="POST" action="{{ url('/logout') }}" style="margin-left:auto;">
        @csrf
        <button type="submit" class="theme-toggle">Keluar</button>
      </form>
    </div>

    <section class="page active">
      @if (session('status'))
        <div class="saya-status">{{ session('status') }}</div>
      @endif

      @yield('content')
    </section>
  </main>
</div>

<div class="toast" id="toast"></div>

<script>window.SAYA_NIP = @json($pegawai['nip'] ?? null);</script>
<script src="{{ asset('js/saya-nav.js') }}"></script>
@yield('scripts')
</body>
</html>
