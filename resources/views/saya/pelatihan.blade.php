@extends('layouts.saya')

@section('title', 'Rekomendasi & Pelatihan Wajib')
@section('subtitle', 'Pilih pelatihan yang ingin/sudah Anda ikuti')

@section('content')

@php
  $wajibByNama = collect($pegawai['pelatihan_wajib'])->keyBy('nama_pelatihan');
  $jumlahDipilih = count($dipilihTikNama) + count($dipilihWajibNama);
@endphp

<div class="tiles" style="margin-bottom:22px;">
  <div class="tile">
    <div class="label">Pelatihan Dipilih</div>
    <div class="value">{{ $jumlahDipilih }}</div>
    <div class="delta">{{ $jumlahDipilih > 0 ? 'tersimpan sebagai pilihan Anda' : 'belum ada yang dipilih -- centang di bawah' }}</div>
  </div>
</div>

@if ($jumlahDipilih > 0)
  <div class="card">
    <div class="profile-section-title" style="margin-top:0;">Pelatihan yang Sudah Dipilih ({{ $jumlahDipilih }})</div>
    <div class="card-sub">Klik "Hapus" untuk membatalkan pilihan -- atau ubah langsung centangnya di daftar checklist di bawah.</div>
    <div class="pelatihan-checklist">
      @foreach ($dipilihTikNama as $nama)
        <div class="pelatihan-check-row" style="cursor:default; justify-content:space-between; align-items:center;">
          <span>
            <span class="pill tik" style="margin-right:6px;">TIK</span>
            <span class="pelatihan-nama">{{ $nama }}</span>
          </span>
          <button type="button" class="btn small" data-hapus-pilihan data-group="tik" data-nama="{{ $nama }}">Hapus</button>
        </div>
      @endforeach
      @foreach ($dipilihWajibNama as $nama)
        <div class="pelatihan-check-row" style="cursor:default; justify-content:space-between; align-items:center;">
          <span>
            <span class="pill" style="margin-right:6px;">{{ $wajibByNama[$nama]['level'] ?? 'Wajib' }}</span>
            <span class="pelatihan-nama">{{ $nama }}</span>
          </span>
          <button type="button" class="btn small" data-hapus-pilihan data-group="wajib" data-nama="{{ $nama }}">Hapus</button>
        </div>
      @endforeach
    </div>
  </div>
@endif

@if ($pegawai['rekomendasi_pelatihan_umum'])
  <div class="card">
    <div class="profile-section-title" style="margin-top:0;">Rekomendasi Pelatihan</div>
    <div class="profile-diklat-item">
      <div class="dname">{{ $pegawai['rekomendasi_pelatihan_umum'] }}</div>
      <div class="dmeta">Jabatan Anda saat ini ({{ $pegawai['jabatan'] ?: '-' }}) tidak menyinggung bidang gelar {{ $pegawai['bidang_gelar'] }} -- pelatihan yang WAJIB diikuti adalah yang sesuai PEKERJAAN SEKARANG, lihat di bawah.</div>
    </div>
  </div>
@endif

@php $opsi = $pegawai['rekomendasi_pelatihan_opsi']; $wajib = $pegawai['pelatihan_wajib']; @endphp
@if (($opsi && count($opsi['pilihan'])) || count($wajib))
  <div class="card">
    <div class="profile-section-title" style="margin-top:0;">Pilih Pelatihan</div>
    <div class="card-sub">Centang pelatihan yang ingin/sudah Anda ikuti, lalu klik "Simpan Pilihan".</div>
    <form id="pelatihan-form" data-okupasi-tik="{{ $opsi['nama_okupasi'] ?? '' }}">
      @if ($opsi && count($opsi['pilihan']))
        <div class="profile-section-title" style="margin-top:16px;">Rekomendasi TIK: <b>{{ $opsi['nama_okupasi'] }}</b>{{ $opsi['kode'] ? ' ('.$opsi['kode'].')' : '' }}</div>
        <div class="pelatihan-checklist">
          @foreach ($opsi['pilihan'] as $t)
            <label class="pelatihan-check-row">
              <input type="checkbox" data-group="tik" data-nama="{{ $t['nama'] }}" data-kode="{{ $t['kode_standar'] }}" {{ in_array($t['nama'], $dipilihTikNama) ? 'checked' : '' }}>
              <span>
                <span class="pelatihan-nama">{{ $t['nama'] }}</span>
                @if ($t['kode_standar'])<span class="pelatihan-kode">{{ $t['kode_standar'] }}</span>@endif
              </span>
            </label>
          @endforeach
        </div>
      @endif

      @if (count($wajib))
        <div class="profile-section-title" style="margin-top:16px;">Pelatihan Wajib (Dasar/Menengah/Tinggi) — kategori: {{ $pegawai['jabatan'] ?: '-' }}</div>
        <div class="pelatihan-checklist">
          @foreach ($wajib as $w)
            <label class="pelatihan-check-row">
              <input type="checkbox" data-group="wajib" data-nama="{{ $w['nama_pelatihan'] }}" {{ in_array($w['nama_pelatihan'], $dipilihWajibNama) ? 'checked' : '' }}>
              <span>
                <span class="pill" style="margin-right:6px;">{{ $w['level'] }}</span>
                <span class="pelatihan-nama">{{ $w['nama_pelatihan'] }}</span>
                @if ($w['sudah_diikuti'])
                  <span class="pill good">Sudah</span>
                @else
                  <span class="pill warn">Belum</span>
                @endif
              </span>
            </label>
          @endforeach
        </div>
      @endif

      <div class="modal-actions" style="justify-content:flex-start;">
        <button type="button" class="btn primary" id="pelatihan-submit">Simpan Pilihan</button>
      </div>
    </form>
  </div>
@else
  <div class="card">
    <div class="empty-state" style="padding:30px 10px;">Belum ada rekomendasi pelatihan atau kurikulum wajib untuk jabatan Anda saat ini.</div>
  </div>
@endif

@endsection

@section('scripts')
<script src="{{ asset('js/saya.js') }}"></script>
@endsection
