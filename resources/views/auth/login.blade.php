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
  <div class="auth-card">
    <div class="auth-brand">
      <img src="{{ asset('images/logo-lms.png') }}" alt="Logo Kabupaten Banyuwangi">
      <div>
        <div class="t1">Bangkom ASN</div>
        <div class="t2">Kab. Banyuwangi</div>
      </div>
    </div>

    <form method="POST" action="{{ url('/login') }}">
      @csrf
      <label for="username">NIP / Username</label>
      <input type="text" id="username" name="username" value="{{ old('username') }}" autofocus autocomplete="username" placeholder="Masukkan NIP Anda">

      <label for="password">Password</label>
      <input type="password" id="password" name="password" autocomplete="current-password" placeholder="Masukkan password">

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

</body>
</html>
