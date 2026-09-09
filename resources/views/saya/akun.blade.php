@extends('layouts.saya')

@section('title', 'Pengaturan Akun')
@section('subtitle', 'Data kontak & keamanan akun Anda')

@section('content')

<div class="card" style="margin-bottom:18px;">
  <div class="profile-section-title" style="margin-top:0;">Data Kontak</div>
  <div class="card-sub">Data resmi kepegawaian (jabatan, gelar, golongan, dll) cuma bisa diperbarui admin lewat proses impor data. Anda bisa memperbarui data kontak sendiri di bawah ini.</div>
  <form method="POST" action="{{ route('saya.update') }}">
    @csrf
    @method('PATCH')
    <label for="email">Email</label>
    <input type="email" id="email" name="email" value="{{ old('email', $pegawai['email']) }}" placeholder="nama@contoh.com">
    @error('email') <div class="field-error">{{ $message }}</div> @enderror

    <label for="alamat">Alamat</label>
    <input type="text" id="alamat" name="alamat" value="{{ old('alamat', $pegawai['alamat'] ?? '') }}" placeholder="Alamat domisili">
    @error('alamat') <div class="field-error">{{ $message }}</div> @enderror

    <div class="modal-actions" style="justify-content:flex-start;">
      <button type="submit" class="btn primary">Simpan Data Kontak</button>
    </div>
  </form>
</div>

<div class="card">
  <div class="profile-section-title" style="margin-top:0;">Ganti Password</div>

  @if ($pakaiPasswordDefault)
    <div class="auth-error" style="margin-bottom:14px;">
      Akun Anda masih memakai password default (sama dengan NIP). Segera ganti demi keamanan akun Anda.
    </div>
  @endif

  <form method="POST" action="{{ route('saya.password') }}">
    @csrf
    @method('PUT')
    <label for="password_lama">Password Lama</label>
    <div class="auth-input-wrap">
      <input type="password" id="password_lama" name="password_lama" autocomplete="current-password" style="padding-left:11px;">
      <button type="button" class="auth-input-eye" data-toggle-password="password_lama" aria-label="Tampilkan password lama">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
      </button>
    </div>
    @error('password_lama') <div class="field-error">{{ $message }}</div> @enderror

    <label for="password_baru">Password Baru</label>
    <div class="auth-input-wrap">
      <input type="password" id="password_baru" name="password_baru" autocomplete="new-password" style="padding-left:11px;">
      <button type="button" class="auth-input-eye" data-toggle-password="password_baru" aria-label="Tampilkan password baru">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
      </button>
    </div>
    <div class="table-note" style="margin-top:6px;">Minimal 8 karakter, kombinasi huruf besar/kecil & angka. Tidak boleh sama dengan NIP.</div>
    @error('password_baru') <div class="field-error">{{ $message }}</div> @enderror

    <label for="password_baru_confirmation">Ulangi Password Baru</label>
    <div class="auth-input-wrap">
      <input type="password" id="password_baru_confirmation" name="password_baru_confirmation" autocomplete="new-password" style="padding-left:11px;">
      <button type="button" class="auth-input-eye" data-toggle-password="password_baru_confirmation" aria-label="Tampilkan ulangi password baru">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
      </button>
    </div>

    <div class="modal-actions" style="justify-content:flex-start;">
      <button type="submit" class="btn primary">Ganti Password</button>
    </div>
  </form>
</div>

@endsection
