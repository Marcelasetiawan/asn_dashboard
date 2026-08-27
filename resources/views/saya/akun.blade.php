@extends('layouts.saya')

@section('title', 'Pengaturan Akun')
@section('subtitle', 'Data kontak & keamanan akun Anda')

@section('content')

<div class="card">
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
  <form method="POST" action="{{ route('saya.password') }}">
    @csrf
    @method('PUT')
    <label for="password_lama">Password Lama</label>
    <input type="password" id="password_lama" name="password_lama" autocomplete="current-password">
    @error('password_lama') <div class="field-error">{{ $message }}</div> @enderror

    <label for="password_baru">Password Baru</label>
    <input type="password" id="password_baru" name="password_baru" autocomplete="new-password">
    @error('password_baru') <div class="field-error">{{ $message }}</div> @enderror

    <label for="password_baru_confirmation">Ulangi Password Baru</label>
    <input type="password" id="password_baru_confirmation" name="password_baru_confirmation" autocomplete="new-password">

    <div class="modal-actions" style="justify-content:flex-start;">
      <button type="submit" class="btn primary">Ganti Password</button>
    </div>
  </form>
</div>

@endsection
