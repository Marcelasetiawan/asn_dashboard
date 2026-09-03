<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>Masuk — Dashboard Bangkom ASN</title>
<link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">
</head>
<body>

<div class="auth-page">

  <div class="auth-side">
    <div class="auth-side-brand">
      <img src="{{ asset('images/logo-lms.png') }}" alt="Logo Kabupaten Banyuwangi">
      <span>Bangkom ASN</span>
    </div>
    <div class="auth-side-hero">
      <h1>Pengembangan Kompetensi<br>Aparatur Sipil Negara</h1>
      <p>Sistem terpadu untuk pemetaan okupasi, rekomendasi pelatihan, dan riwayat pengembangan kompetensi ASN Kabupaten Banyuwangi.</p>
    </div>
    <div class="auth-side-footer">&copy; {{ date('Y') }} Badan Kepegawaian dan Pengembangan SDM &mdash; Kabupaten Banyuwangi.</div>
  </div>

  <div class="auth-form-side">
    <div class="auth-form-topbar">
      <button type="button" class="theme-toggle" id="theme-toggle" style="border-color:var(--border);background:var(--surface-2);color:var(--text-secondary);">&#9789;</button>
    </div>

    <div class="auth-form-wrap">
      <h2>Selamat Datang</h2>
      <p class="auth-form-sub">Silakan masuk menggunakan NIP dan kata sandi Anda.</p>

      <form method="POST" action="{{ url('/login') }}">
        @csrf
        <label for="username">Nomor Induk Pegawai (NIP)</label>
        <div class="auth-input-wrap">
          <span class="auth-input-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
          </span>
          <input type="text" id="username" name="username" value="{{ old('username') }}" autofocus autocomplete="username" placeholder="Masukkan NIP Anda">
        </div>

        <label for="password">Kata Sandi</label>
        <div class="auth-input-wrap">
          <span class="auth-input-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
          </span>
          <input type="password" id="password" name="password" autocomplete="current-password" placeholder="Masukkan password">
          <button type="button" class="auth-input-eye" id="togglePassword" aria-label="Tampilkan password">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
          </button>
        </div>

        @if ($errors->any())
          <div class="auth-error">{{ $errors->first() }}</div>
        @endif

        <button type="submit" class="btn primary">Masuk</button>
      </form>

      <div class="auth-hint">
        ASN masuk pakai NIP sebagai username.<br>
        Password awal = NIP Anda sendiri (bisa diganti setelah masuk).
      </div>
    </div>
  </div>

</div>

<script>
(function () {
  "use strict";
  var themeBtn = document.getElementById("theme-toggle");
  var dark = false;
  themeBtn.addEventListener("click", function () {
    dark = !dark;
    if (dark) document.documentElement.setAttribute("data-theme", "dark");
    else document.documentElement.removeAttribute("data-theme");
  });

  var pwd = document.getElementById("password");
  var eyeBtn = document.getElementById("togglePassword");
  eyeBtn.addEventListener("click", function () {
    pwd.type = pwd.type === "password" ? "text" : "password";
  });
})();
</script>

</body>
</html>
