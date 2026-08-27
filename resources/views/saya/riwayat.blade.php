@extends('layouts.saya')

@section('title', 'Riwayat Pelatihan')
@section('subtitle', 'Seluruh riwayat diklat/kursus yang tercatat atas nama Anda -- klik nama diklat untuk lihat detail & unggah sertifikat')

@section('content')

@php
  $kurang = collect($riwayat)->where('sertifikat_lengkap', false)->count();
  $sumberLabel = [
    'diklat_siasn' => 'Diklat SIASN',
    'sertifikasi_asn' => 'Sertifikasi ASN',
    'hasil_akhir_seri_6' => 'ASN Bersinar Seri 6',
    'hasil_akhir_modul' => 'Modul Digital',
  ];
@endphp
@if ($kurang > 0)
  <div class="saya-status" style="background:color-mix(in srgb, var(--status-serious) 14%, transparent); color:var(--status-serious);">
    Ada {{ $kurang }} riwayat yang sertifikatnya belum lengkap -- klik nama diklatnya untuk melengkapi.
  </div>
@endif

<div class="toolbar">
  <input class="filter-input" id="riwayat-saya-search" placeholder="Cari nama diklat atau penyelenggara...">
</div>

@if (!count($riwayat))
  <div class="empty-state" style="padding:40px 10px;"><div class="big">&#128218;</div>Belum ada riwayat pelatihan tercatat.</div>
@else
  <div id="riwayat-saya-list">
    @foreach ($riwayat as $d)
      <div class="profile-diklat-item" data-cari="{{ mb_strtolower($d['nama_diklat'].' '.($d['penyelenggara'] ?? '')) }}" data-riwayat-open="{{ $d['id'] }}" role="button" tabindex="0" style="cursor:pointer;">
        <div class="dname clickable-name">{{ $d['nama_diklat'] }}</div>
        <div class="dmeta">
          {{ $d['jenis_sertifikasi'] ?: '-' }} &middot; {{ $d['penyelenggara'] ?: '-' }} &middot; {{ $d['pelaksanaan'] ?: '-' }} &middot; {{ $d['jp'] ?: 0 }} JP &middot;
          @if ($d['sertifikat_lengkap'])
            <span class="pill good">Sertifikat Lengkap</span>
          @else
            <span class="pill warn">Sertifikat Belum Lengkap</span>
          @endif
        </div>
      </div>
    @endforeach
  </div>
  <div class="table-note" id="riwayat-saya-count">Menampilkan {{ count($riwayat) }} dari {{ count($riwayat) }} riwayat.</div>

  @foreach ($riwayat as $d)
    @php $gagalDiSini = old('riwayat_id') == $d['id'] && $errors->any(); @endphp
    <div class="modal-backdrop {{ $gagalDiSini ? 'open' : '' }}" id="riwayat-modal-{{ $d['id'] }}" data-riwayat-modal>
      <div class="modal modal-wide">
        <button type="button" class="profile-close" data-riwayat-close aria-label="Tutup">&times;</button>
        <h3>{{ $d['nama_diklat'] }}</h3>
        <div class="sub">{{ $d['jenis_sertifikasi'] ?: '-' }} &middot; {{ $d['penyelenggara'] ?: '-' }}</div>

        <div class="profile-kv">
          <div class="kv-item"><div class="kv-label">Pelaksanaan</div><div class="kv-val">{{ $d['pelaksanaan'] ?: '-' }}</div></div>
          <div class="kv-item"><div class="kv-label">Jumlah Jam (JP)</div><div class="kv-val">{{ $d['jp'] ?: 0 }}</div></div>
          <div class="kv-item"><div class="kv-label">No. Sertifikat</div><div class="kv-val">{{ $d['no_sertifikat'] && $d['no_sertifikat'] !== '-' ? $d['no_sertifikat'] : '-' }}</div></div>
          <div class="kv-item"><div class="kv-label">Status Sertifikat</div><div class="kv-val">{{ $d['sertifikat_lengkap'] ? 'Lengkap' : 'Belum Lengkap' }}</div></div>
          @if ($d['status_crawl'])
            <div class="kv-item"><div class="kv-label">Status Data</div><div class="kv-val">{{ $d['status_crawl'] }}</div></div>
          @endif
          @if ($d['sumber'])
            <div class="kv-item"><div class="kv-label">Sumber</div><div class="kv-val">{{ $sumberLabel[$d['sumber']] ?? $d['sumber'] }}</div></div>
          @endif
        </div>

        @if ($d['berkas_url'])
          @php $ext = strtolower(pathinfo($d['berkas_url'], PATHINFO_EXTENSION)); @endphp
          <div style="margin-bottom:14px;">
            <div class="profile-section-title" style="margin:6px 0 8px;">Berkas Sertifikat Tersimpan</div>
            @if (in_array($ext, ['jpg', 'jpeg', 'png']))
              <a href="{{ $d['berkas_url'] }}" target="_blank" rel="noopener">
                <img src="{{ $d['berkas_url'] }}" alt="Berkas sertifikat {{ $d['nama_diklat'] }}" style="max-width:100%; max-height:340px; border-radius:8px; border:1px solid var(--border); display:block;">
              </a>
              <div class="dmeta" style="margin-top:5px;">Klik gambar untuk membuka ukuran penuh di tab baru.</div>
            @else
              <a href="{{ $d['berkas_url'] }}" target="_blank" rel="noopener" class="btn small">Buka Berkas PDF</a>
            @endif
          </div>
        @endif

        <div class="profile-section-title" style="margin-top:6px;">{{ $d['sertifikat_lengkap'] ? 'Perbarui Sertifikat' : 'Unggah Sertifikat' }}</div>
        @if ($gagalDiSini)
          <div class="saya-status" style="background:color-mix(in srgb, var(--status-serious) 14%, transparent); color:var(--status-serious);">
            {{ $errors->first() }}
          </div>
        @endif
        <form method="POST" action="{{ route('saya.sertifikat', $d['id']) }}" enctype="multipart/form-data">
          @csrf
          <input type="hidden" name="riwayat_id" value="{{ $d['id'] }}">
          <label for="no_sertifikat_{{ $d['id'] }}">Nomor Sertifikat</label>
          <input type="text" id="no_sertifikat_{{ $d['id'] }}" name="no_sertifikat" required value="{{ $gagalDiSini ? old('no_sertifikat') : ($d['no_sertifikat'] !== '-' ? $d['no_sertifikat'] : '') }}" placeholder="Contoh: 800/1234/429.204/2026">
          <label for="berkas_{{ $d['id'] }}">Berkas Sertifikat (PDF/JPG/PNG, maks 5MB)</label>
          <input type="file" id="berkas_{{ $d['id'] }}" name="berkas" accept=".pdf,.jpg,.jpeg,.png">
          <div class="modal-actions" style="justify-content:flex-start;">
            <button type="submit" class="btn small primary">Simpan Sertifikat</button>
          </div>
        </form>
      </div>
    </div>
  @endforeach
@endif

@endsection

@section('scripts')
<script>
(function () {
  var input = document.getElementById("riwayat-saya-search");
  if (input) {
    var items = Array.prototype.slice.call(document.querySelectorAll("#riwayat-saya-list [data-cari]"));
    var countEl = document.getElementById("riwayat-saya-count");
    input.addEventListener("input", function () {
      var q = input.value.toLowerCase().trim();
      var shown = 0;
      items.forEach(function (el) {
        var match = !q || el.getAttribute("data-cari").indexOf(q) >= 0;
        el.style.display = match ? "" : "none";
        if (match) shown++;
      });
      if (countEl) countEl.textContent = "Menampilkan " + shown + " dari " + items.length + " riwayat.";
    });
  }

  function openModal(id) {
    var modal = document.getElementById("riwayat-modal-" + id);
    if (modal) modal.classList.add("open");
  }
  function closeModal(backdrop) {
    backdrop.classList.remove("open");
  }

  document.querySelectorAll("[data-riwayat-open]").forEach(function (el) {
    el.addEventListener("click", function () { openModal(el.getAttribute("data-riwayat-open")); });
    el.addEventListener("keydown", function (e) {
      if (e.key === "Enter" || e.key === " ") { e.preventDefault(); openModal(el.getAttribute("data-riwayat-open")); }
    });
  });

  document.querySelectorAll("[data-riwayat-modal]").forEach(function (bd) {
    bd.addEventListener("click", function (e) { if (e.target === bd) closeModal(bd); });
  });

  document.querySelectorAll("[data-riwayat-close]").forEach(function (btn) {
    btn.addEventListener("click", function () { closeModal(btn.closest(".modal-backdrop")); });
  });

  document.addEventListener("keydown", function (e) {
    if (e.key !== "Escape") return;
    document.querySelectorAll(".modal-backdrop.open").forEach(closeModal);
  });
})();
</script>
@endsection
