@extends('layouts.saya')

@section('title', 'Profil')
@section('subtitle', 'Data diri Anda sebagai ASN')

@section('content')

<div class="card">
  <div class="profile-head">
    <div class="profile-avatar">{{ strtoupper(substr($pegawai['nama_bersih'] ?? $pegawai['nama'], 0, 1)) }}</div>
    <div>
      <h3>{{ $pegawai['nama'] }}</h3>
      <div class="role">{{ $pegawai['jabatan'] ?: '-' }} &middot; {{ $pegawai['satuan_kerja'] ?: '-' }}</div>
    </div>
  </div>
  <div class="profile-flags">
    <span class="pill {{ $pegawai['kelompok'] === 'TIK' ? 'tik' : ($pegawai['kelompok'] === 'Manajerial' ? 'manajerial' : 'nontik') }}">{{ $pegawai['kelompok'] }}</span>
    @if ($pegawai['sudah_diklat'])
      <span class="pill good">{{ $pegawai['jumlah_diklat'] }} Diklat Diikuti</span>
    @else
      <span class="pill warn">Belum Pernah Diklat</span>
    @endif
  </div>
  <div class="profile-kv">
    <div class="kv-item"><div class="kv-label">NIP</div><div class="kv-val">{{ $pegawai['nip'] }}</div></div>
    <div class="kv-item"><div class="kv-label">Jenis Kelamin</div><div class="kv-val">{{ $pegawai['jenis_kelamin'] === 'L' ? 'Laki-laki' : ($pegawai['jenis_kelamin'] === 'P' ? 'Perempuan' : '-') }}</div></div>
    <div class="kv-item"><div class="kv-label">Status Kepegawaian</div><div class="kv-val">{{ $pegawai['status'] ?: '-' }}</div></div>
    <div class="kv-item"><div class="kv-label">Golongan / Ruang</div><div class="kv-val">{{ $pegawai['golongan_ruang'] ?: '-' }}</div></div>
    <div class="kv-item"><div class="kv-label">Eselon</div><div class="kv-val">{{ $pegawai['eselon'] ?: '- (Non Struktural)' }}</div></div>
    <div class="kv-item"><div class="kv-label">Satuan Kerja / OPD</div><div class="kv-val">{{ $pegawai['satuan_kerja'] ?: '-' }}</div></div>
    <div class="kv-item"><div class="kv-label">Pendidikan Terakhir</div><div class="kv-val">{{ $pegawai['pendidikan'] ?: '-' }}{{ $pegawai['tahun_lulus'] ? ' (lulus '.$pegawai['tahun_lulus'].')' : '' }}</div></div>
    <div class="kv-item"><div class="kv-label">Gelar Depan</div><div class="kv-val">{{ $pegawai['gelar_depan'] ?: '-' }}</div></div>
    <div class="kv-item"><div class="kv-label">Gelar Belakang</div><div class="kv-val">{{ $pegawai['gelar_belakang'] ?: '-' }}</div></div>
    <div class="kv-item"><div class="kv-label">Total JP Diklat</div><div class="kv-val">{{ number_format($pegawai['total_jp'], 0, ',', '.') }} JP</div></div>
    @if ($pegawai['jabatan_fungsional_spesifik'])
      <div class="kv-item"><div class="kv-label">Jabatan Fungsional Spesifik</div><div class="kv-val">{{ $pegawai['jabatan_fungsional_spesifik'] }}</div></div>
    @endif
    @if ($pegawai['unor_detail'])
      <div class="kv-item"><div class="kv-label">Unit Kerja Detail</div><div class="kv-val">{{ $pegawai['unor_detail'] }}</div></div>
    @endif
    <div class="kv-item"><div class="kv-label">Email</div><div class="kv-val">{{ $pegawai['email'] ?: '-' }}</div></div>
    <div class="kv-item"><div class="kv-label">Alamat</div><div class="kv-val">{{ $pegawai['alamat'] ?? '-' }}</div></div>
  </div>
  <div class="table-note">Data resmi kepegawaian di atas cuma bisa diperbarui admin lewat proses impor data. Untuk ubah email/alamat, buka menu <a href="{{ route('saya.akun') }}">Pengaturan Akun</a>.</div>
</div>

@endsection
