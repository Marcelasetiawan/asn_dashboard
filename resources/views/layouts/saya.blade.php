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

<div class="app">

  <aside class="sidebar" id="sidebar">
    <div class="brand">
      <div class="brand-mark"><img src="{{ asset('images/logo-lms.png') }}" alt="Logo Kabupaten Banyuwangi"></div>
      <div class="brand-text">
        <div class="t1">Bangkom ASN</div>
        <div class="t2">Kab. Banyuwangi</div>
      </div>
      <button class="sidebar-toggle" aria-label="Lipat/buka menu">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><line x1="9" y1="3" x2="9" y2="21"/></svg>
      </button>
    </div>

    <div class="nav-group">
      <a class="nav-item {{ request()->routeIs('saya') ? 'active' : '' }}" href="{{ route('saya') }}"><span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9.5L12 3l9 6.5"/><path d="M5 10v10a1 1 0 0 0 1 1h4v-6h4v6h4a1 1 0 0 0 1-1V10"/></svg></span>Ringkasan</a>
      <a class="nav-item {{ request()->routeIs('saya.profil') ? 'active' : '' }}" href="{{ route('saya.profil') }}"><span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 3.6-7 8-7s8 3 8 7"/></svg></span>Profil</a>
      <a class="nav-item {{ request()->routeIs('saya.riwayat') ? 'active' : '' }}" href="{{ route('saya.riwayat') }}"><span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></span>Riwayat Pelatihan</a>
      <a class="nav-item {{ request()->routeIs('saya.pelatihan') ? 'active' : '' }}" href="{{ route('saya.pelatihan') }}"><span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg></span>Rekomendasi &amp; Pelatihan Wajib</a>
      <a class="nav-item {{ request()->routeIs('saya.akun') ? 'active' : '' }}" href="{{ route('saya.akun') }}"><span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg></span>Pengaturan Akun</a>
    </div>

    <div class="sidebar-footer">
      <form method="POST" action="{{ url('/logout') }}" data-logout-form>
        @csrf
        <button type="submit" class="nav-item logout-btn">
          <span class="ic">&#10162;</span> Keluar
        </button>
      </form>
    </div>
  </aside>

  <main class="content">
    <div class="topbar">
      <button class="sidebar-toggle topbar-toggle" aria-label="Buka menu">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><line x1="9" y1="3" x2="9" y2="21"/></svg>
      </button>
      <div>
        <h1>@yield('title', 'Ringkasan')</h1>
        <div class="sub">@yield('subtitle', '')</div>
      </div>
      <details class="user-badge-details" style="margin-left:auto;">
        <summary class="user-badge">
          <div class="user-avatar">{{ strtoupper(substr($pegawai['nama_bersih'] ?? $pegawai['nama'] ?? 'A', 0, 1)) }}</div>
          <div class="user-meta">
            <div class="u-name">{{ $pegawai['nama_bersih'] ?? $pegawai['nama'] ?? '-' }}</div>
            <div class="u-date">{{ now()->format('l, d F Y') }}</div>
          </div>
        </summary>
        <div class="user-badge-menu">
          <form method="POST" action="{{ url('/logout') }}" data-logout-form>
            @csrf
            <button type="submit">Keluar</button>
          </form>
        </div>
      </details>
    </div>

    <section class="page active">
      @if (session('status'))
        <div class="saya-status">{{ session('status') }}</div>
      @endif

      @yield('content')
    </section>
  </main>
</div>

<div class="modal-backdrop" id="logout-modal">
  <div class="modal">
    <h3>Keluar dari akun?</h3>
    <div class="sub">Anda perlu masuk lagi untuk mengakses portal ini.</div>
    <div class="modal-actions">
      <button type="button" class="btn" id="logout-cancel">Batal</button>
      <button type="button" class="btn primary" id="logout-confirm" style="background:var(--status-critical);border-color:var(--status-critical);">Ya, Keluar</button>
    </div>
  </div>
</div>
<div class="toast" id="toast"></div>

<script>window.SAYA_NIP = @json($pegawai['nip'] ?? null);</script>
<script src="{{ asset('js/saya-nav.js') }}"></script>
@yield('scripts')
</body>
</html>
