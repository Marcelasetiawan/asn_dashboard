@extends('layouts.saya')

@section('title', 'Ringkasan')
@section('subtitle', 'Halo, ' . ($pegawai['nama_bersih'] ?? $pegawai['nama']) . ' — ini gambaran singkat Anda')

@section('content')

@php
  $wajibTotal = $pegawai['pelatihan_wajib_total'];
  $wajibTerpenuhi = $pegawai['pelatihan_wajib_terpenuhi'];
  $wajibPct = $wajibTotal > 0 ? $wajibTerpenuhi / $wajibTotal : 0;
  $circumference = 2 * M_PI * 50;
  $selisihJp = $pegawai['total_jp'] - $rataRataJp;
@endphp

<div class="tiles">
  <div class="tile">
    <div class="label">Diklat Diikuti</div>
    <div class="value">{{ $pegawai['jumlah_diklat'] }}</div>
    <div class="delta">{{ $pegawai['sudah_diklat'] ? 'riwayat tercatat' : 'belum pernah ikut diklat' }}</div>
  </div>
  <div class="tile">
    <div class="label">Total JP Diklat Anda</div>
    <div class="value">{{ number_format($pegawai['total_jp'], 0, ',', '.') }}</div>
    <div class="delta {{ $selisihJp >= 0 ? '' : 'warn' }}">
      rata-rata seluruh ASN: {{ number_format($rataRataJp, 1, ',', '.') }} JP
      ({{ $selisihJp >= 0 ? '+' : '' }}{{ number_format($selisihJp, 1, ',', '.') }})
    </div>
  </div>
  <div class="tile">
    <div class="label">Sertifikat Belum Lengkap</div>
    <div class="value">{{ $pegawai['sertifikat_kurang'] }}</div>
    <div class="delta {{ $pegawai['sertifikat_kurang'] > 0 ? 'warn' : '' }}">dari {{ $pegawai['jumlah_diklat'] }} riwayat diklat</div>
  </div>
  <div class="tile">
    <div class="label">Pelatihan Dipilih</div>
    <div class="value">{{ $jumlahDipilih }}</div>
    <div class="delta">{{ $jumlahDipilih > 0 ? 'sudah Anda pilih untuk diikuti' : 'belum ada yang dipilih' }}</div>
  </div>
  <div class="tile">
    <div class="label">Pelatihan Wajib Terpenuhi</div>
    <div class="value">{{ $wajibTerpenuhi }}/{{ $wajibTotal }}</div>
    <div class="delta">kategori: {{ $pegawai['jabatan'] ?: '-' }}</div>
  </div>
</div>

<div class="grid-2">
  <div class="card">
    <h3>Status Pelatihan Wajib</h3>
    <div class="card-sub">Perbandingan yang sudah terpenuhi vs yang masih belum</div>
    @if ($wajibTotal > 0)
      <div class="pie-wrap">
        <div class="pie-svg-holder">
          <svg viewBox="0 0 120 120" width="150" height="150">
            <circle cx="60" cy="60" r="50" fill="none" stroke="var(--gridline)" stroke-width="16"/>
            <circle cx="60" cy="60" r="50" fill="none" stroke="var(--status-good)" stroke-width="16"
              stroke-dasharray="{{ $wajibPct * $circumference }} {{ $circumference }}"
              stroke-linecap="round" transform="rotate(-90 60 60)"/>
          </svg>
          <div class="pie-center">
            <div class="pie-center-value">{{ round($wajibPct * 100) }}%</div>
            <div class="pie-center-label">Terpenuhi</div>
          </div>
        </div>
        <div class="pie-legend">
          <div class="pie-legend-row"><span class="dot" style="background:var(--status-good);"></span><span class="pie-legend-label">Terpenuhi</span><span class="pie-legend-val">{{ $wajibTerpenuhi }}</span></div>
          <div class="pie-legend-row"><span class="dot" style="background:var(--gridline);"></span><span class="pie-legend-label">Belum</span><span class="pie-legend-val">{{ $wajibTotal - $wajibTerpenuhi }}</span></div>
        </div>
      </div>
      <a href="{{ route('saya.pelatihan') }}" class="btn small primary" style="margin-top:10px;">Lihat &amp; Pilih Pelatihan</a>
    @else
      <div class="empty-state" style="padding:30px 10px;">Belum ada kurikulum Pelatihan Wajib untuk kategori jabatan Anda.</div>
    @endif
  </div>

  <div class="card">
    <h3>Rekomendasi Pelatihan</h3>
    <div class="card-sub">Arah pengembangan kompetensi Anda</div>
    @if ($pegawai['rekomendasi_pelatihan_opsi'])
      <div class="profile-diklat-item">
        <div class="dname">{{ $pegawai['rekomendasi_pelatihan_opsi']['nama_okupasi'] }}</div>
        <div class="dmeta">{{ $pegawai['rekomendasi_pelatihan_opsi']['area'] ?? '-' }}</div>
      </div>
      @if ($pegawai['rekomendasi_pelatihan_umum'])
        <div class="profile-diklat-item">
          <div class="dname">{{ $pegawai['rekomendasi_pelatihan_umum'] }}</div>
        </div>
      @endif
      <a href="{{ route('saya.pelatihan') }}" class="btn small primary" style="margin-top:4px;">Pilih Pelatihan</a>
    @else
      <div class="empty-state" style="padding:30px 10px;">Belum ada rekomendasi okupasi untuk jabatan Anda saat ini.</div>
    @endif
  </div>
</div>

@endsection
