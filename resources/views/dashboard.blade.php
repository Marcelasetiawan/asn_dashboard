<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>Dashboard Bangkom ASN — Kabupaten Banyuwangi</title>
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
      <div class="nav-item active" data-page="ringkasan"><span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9.5L12 3l9 6.5"/><path d="M5 10v10a1 1 0 0 0 1 1h4v-6h4v6h4a1 1 0 0 0 1-1V10"/></svg></span>Dashboard</div>

      <div class="nav-sep"></div>
      <div class="nav-label">Manajemen ASN</div>
      <div class="nav-item" data-toggle-submenu="profil"><span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="7" r="4"/><path d="M1 21v-2a4 4 0 0 1 4-4h4a4 4 0 0 1 4 4v2"/><path d="M17 3.13a4 4 0 0 1 0 7.75"/><path d="M23 21v-2a4 4 0 0 0-3-3.85"/></svg></span>Profil Pegawai</div>
      <div class="submenu" id="submenu-profil">
        <div class="nav-item" data-page="profil" data-kelompok="TIK">ASN TIK</div>
        <div class="nav-item" data-page="profil" data-kelompok="Non TIK">ASN Non TIK</div>
        <div class="nav-item" data-page="profil" data-kelompok="Manajerial">ASN Manajerial</div>
      </div>

      <div class="nav-item" data-page="sertifikat"><span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg></span>Upload Sertifikat</div>
      <div class="nav-item" data-page="bersertifikat"><span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="7"/><polyline points="8.21 13.89 7 23 12 20 17 23 15.79 13.88"/></svg></span>Sudah Bersertifikat</div>
      <div class="nav-item" data-page="riwayat"><span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></span>Riwayat Kursus</div>
      <div class="nav-item" data-page="caridiklat"><span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg></span>Cari Diklat</div>
      <div class="nav-item" data-page="sudah"><span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg></span>Sudah Pelatihan</div>
      <div class="nav-item" data-page="belum"><span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg></span>Belum Pelatihan</div>
      <div class="nav-item" data-page="rekomendasi"><span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg></span>Rekomendasi Pelatihan</div>

      <div class="nav-sep"></div>
      <div class="nav-label">Rekapitulasi</div>
      <div class="nav-item" data-page="opd"><span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg></span>ASN per OPD</div>
      <div class="nav-item" data-page="golongan"><span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg></span>ASN per Golongan</div>
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
        <h1 id="topbar-title">Dashboard</h1>
        <div class="sub" id="topbar-sub">Gambaran umum kondisi SDM &amp; pengembangan kompetensi</div>
      </div>
      <button class="theme-toggle" id="theme-toggle" style="margin-left:auto;">&#9789; Mode</button>
      <details class="user-badge-details">
        <summary class="user-badge">
          <div class="user-avatar">{{ strtoupper(substr(Auth::user()->name ?? 'A', 0, 1)) }}</div>
          <div class="user-meta">
            <div class="u-name">{{ Auth::user()->name ?? 'Admin' }}</div>
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

    <!-- ============ RINGKASAN ============ -->
    <section class="page active" id="page-ringkasan">
      <div class="welcome-banner">
        <div class="wb-date">{{ now()->format('l, d F Y') }}</div>
        <h2>Selamat datang, {{ Auth::user()->name ?? 'Admin' }}</h2>
        <p>Badan Kepegawaian dan Pengembangan Sumber Daya Manusia &mdash; Kabupaten Banyuwangi</p>
      </div>
      <div class="tiles" id="tiles-ringkasan"></div>
      <div class="grid-2">
        <div class="card">
          <h3>Sebaran Kelompok ASN</h3>
          <div class="card-sub">Klasifikasi berdasarkan jabatan &amp; eselon</div>
          <div id="chart-kelompok"></div>
        </div>
        <div class="card">
          <h3>10 OPD dengan ASN Terbanyak</h3>
          <div class="card-sub">Jumlah pegawai per organisasi perangkat daerah</div>
          <div id="chart-opd-top"></div>
        </div>
      </div>
      <div class="grid-2">
        <div class="card">
          <h3>Sebaran Golongan Ruang</h3>
          <div class="card-sub">Jumlah pegawai per golongan</div>
          <div id="chart-golongan"></div>
        </div>
        <div class="card">
          <h3>Status Pengembangan Kompetensi</h3>
          <div class="card-sub">Perbandingan sudah vs belum mengikuti diklat</div>
          <div id="chart-diklat-status"></div>
        </div>
      </div>
    </section>

    <!-- ============ PROFIL PEGAWAI ============ -->
    <section class="page" id="page-profil">
      <div class="page-head">
        <h2>Profil Pegawai</h2>
        <p>Data ASN dikelompokkan menjadi TIK, Non TIK, dan Manajerial berdasarkan jabatan &amp; eselon.</p>
      </div>
      <div class="tabs" id="profil-tabs"></div>
      <div class="card" style="margin-bottom:18px;">
        <h3 id="profil-chart-title">Sebaran Golongan Ruang</h3>
        <div class="card-sub" id="profil-chart-sub">Jumlah pegawai per golongan pada kelompok yang dipilih</div>
        <div id="chart-profil-golongan"></div>
      </div>
      <div class="toolbar">
        <input class="filter-input" id="profil-search" placeholder="Cari nama, NIP, jabatan, atau satuan kerja...">
        <select class="filter-select" id="profil-filter-opd"><option value="">Semua OPD</option></select>
        <select class="filter-select" id="profil-filter-golongan"><option value="">Semua Golongan</option></select>
        <select class="filter-select" id="profil-limit">
          <option value="500" selected>Tampilkan 500 baris</option>
          <option value="1000">Tampilkan 1000 baris</option>
          <option value="2000">Tampilkan 2000 baris</option>
          <option value="all">Tampilkan semua</option>
        </select>
      </div>
      <div class="table-wrap"><table class="data-table" id="table-profil">
        <thead><tr>
          <th>No.</th><th>NIP</th><th>Nama</th><th>Jabatan</th><th>Satuan Kerja</th><th>Golongan</th><th>Diklat</th><th>Kelompok</th>
        </tr></thead>
        <tbody></tbody>
      </table></div>
      <div class="table-note" id="profil-count"></div>
    </section>

    <!-- ============ UPLOAD SERTIFIKAT ============ -->
    <section class="page" id="page-sertifikat">
      <div class="page-head">
        <h2>Upload Sertifikat</h2>
        <p>Daftar riwayat pelatihan yang <strong>belum</strong> memiliki nomor sertifikat / berkas sertifikat terunggah.</p>
      </div>
      <div class="tiles" id="tiles-sertifikat"></div>
      <div class="card" style="margin-bottom:18px;">
        <h3>Tunggakan Sertifikat per Kelompok</h3>
        <div class="card-sub">Jumlah riwayat diklat yang berkasnya belum lengkap, per kelompok ASN</div>
        <div id="chart-sertifikat-kelompok"></div>
      </div>
      <div class="toolbar">
        <input class="filter-input" id="sertifikat-search" placeholder="Cari nama pegawai atau nama pelatihan...">
      </div>
      <div class="table-wrap"><table class="data-table" id="table-sertifikat">
        <thead><tr>
          <th>No.</th><th>Nama Pegawai</th><th>Satuan Kerja</th><th>Nama Diklat</th><th>Penyelenggara</th><th>Pelaksanaan</th><th>Status</th><th></th>
        </tr></thead>
        <tbody></tbody>
      </table></div>
      <div class="table-note" id="sertifikat-count"></div>
    </section>

    <!-- ============ SUDAH BERSERTIFIKAT ============ -->
    <section class="page" id="page-bersertifikat">
      <div class="page-head">
        <h2>Sudah Bersertifikat</h2>
        <p>ASN yang sudah mengikuti pelatihan/diklat <strong>dan</strong> memiliki sertifikat lengkap untuk minimal satu pelatihan tersebut.</p>
      </div>
      <div class="tiles" id="tiles-bersertifikat"></div>
      <div class="card" style="margin-bottom:18px;">
        <h3>Sebaran Kelompok ASN Bersertifikat</h3>
        <div class="card-sub">Jumlah ASN bersertifikat lengkap, per kelompok</div>
        <div id="chart-bersertifikat-kelompok"></div>
      </div>
      <div class="toolbar">
        <input class="filter-input" id="bersertifikat-search" placeholder="Cari nama, NIP, jabatan, atau satuan kerja...">
        <select class="filter-select" id="bersertifikat-filter-kelompok">
          <option value="">Semua Kelompok</option>
          <option value="TIK">ASN TIK</option>
          <option value="Non TIK">ASN Non TIK</option>
          <option value="Manajerial">ASN Manajerial</option>
        </select>
        <select class="filter-select" id="bersertifikat-limit">
          <option value="500" selected>Tampilkan 500 baris</option>
          <option value="1000">Tampilkan 1000 baris</option>
          <option value="2000">Tampilkan 2000 baris</option>
          <option value="all">Tampilkan semua</option>
        </select>
      </div>
      <div class="table-wrap"><table class="data-table" id="table-bersertifikat">
        <thead><tr>
          <th>No.</th><th>NIP</th><th>Nama</th><th>Jabatan</th><th>Satuan Kerja</th><th>Diklat Diikuti</th><th>Sertifikat Lengkap</th><th>Kelompok</th>
        </tr></thead>
        <tbody></tbody>
      </table></div>
      <div class="table-note" id="bersertifikat-count"></div>
    </section>

    <!-- ============ RIWAYAT KURSUS ============ -->
    <section class="page" id="page-riwayat">
      <div class="page-head">
        <h2>Riwayat Kursus &amp; Diklat</h2>
        <p>Seluruh riwayat pelatihan yang tercatat, per pegawai.</p>
      </div>
      <div class="card" style="margin-bottom:18px;">
        <h3>Riwayat Diklat per Jenis Sertifikasi</h3>
        <div class="card-sub">Jumlah baris riwayat berdasarkan jenis (kursus, diklat, bimtek, dst)</div>
        <div id="chart-riwayat-jenis"></div>
      </div>
      <div class="toolbar">
        <input class="filter-input" id="riwayat-search" placeholder="Cari nama pegawai, nama diklat, atau penyelenggara...">
        <select class="filter-select" id="riwayat-filter-jenis"><option value="">Semua Jenis</option></select>
        <select class="filter-select" id="riwayat-limit">
          <option value="500" selected>Tampilkan 500 baris</option>
          <option value="1000">Tampilkan 1000 baris</option>
          <option value="2000">Tampilkan 2000 baris</option>
          <option value="all">Tampilkan semua</option>
        </select>
      </div>
      <div class="table-wrap"><table class="data-table" id="table-riwayat">
        <thead><tr>
          <th>No.</th><th>Nama Pegawai</th><th>Jenis</th><th>Nama Diklat</th><th>Penyelenggara</th><th>Pelaksanaan</th><th>JP</th><th>Sertifikat</th><th>Berkas</th>
        </tr></thead>
        <tbody></tbody>
      </table></div>
      <div class="table-note" id="riwayat-count"></div>
    </section>

    <!-- ============ CARI DIKLAT ============ -->
    <section class="page" id="page-caridiklat">
      <div class="page-head">
        <h2>Cari Diklat</h2>
        <p>Cari program diklat/pelatihan yang pernah diselenggarakan, dan lihat siapa saja pesertanya.</p>
      </div>
      <div class="tiles" id="tiles-caridiklat"></div>
      <div class="card" style="margin-bottom:18px;">
        <h3>10 Program Diklat dengan Peserta Terbanyak</h3>
        <div class="card-sub">Jumlah peserta per program diklat/pelatihan</div>
        <div id="chart-caridiklat-top"></div>
      </div>
      <div class="toolbar">
        <input class="filter-input" id="caridiklat-search" placeholder="Cari nama diklat atau penyelenggara...">
        <select class="filter-select" id="caridiklat-filter-jenis"><option value="">Semua Jenis</option></select>
      </div>
      <div class="table-wrap"><table class="data-table" id="table-caridiklat">
        <thead><tr>
          <th>Nama Diklat</th><th>Jenis</th><th>Penyelenggara</th><th>Jumlah Peserta</th><th>Total JP</th><th>Sertifikat Lengkap</th>
        </tr></thead>
        <tbody></tbody>
      </table></div>
      <div class="table-note" id="caridiklat-count"></div>
    </section>

    <!-- ============ SUDAH PELATIHAN ============ -->
    <section class="page" id="page-sudah">
      <div class="page-head">
        <h2>ASN Sudah Mengikuti Pelatihan</h2>
        <p>Pegawai yang tercatat pernah mengikuti minimal satu diklat/kursus, lengkap atau belum sertifikatnya.</p>
      </div>
      <div class="tiles" id="tiles-sudah"></div>
      <div class="card" style="margin-bottom:18px;">
        <h3>Sebaran Kelompok ASN Sudah Pelatihan</h3>
        <div class="card-sub">Jumlah ASN yang sudah pernah diklat, per kelompok</div>
        <div id="chart-sudah-kelompok"></div>
      </div>
      <div class="toolbar">
        <input class="filter-input" id="sudah-search" placeholder="Cari nama, NIP, jabatan, atau satuan kerja...">
        <select class="filter-select" id="sudah-filter-kelompok">
          <option value="">Semua Kelompok</option>
          <option value="TIK">ASN TIK</option>
          <option value="Non TIK">ASN Non TIK</option>
          <option value="Manajerial">ASN Manajerial</option>
        </select>
        <select class="filter-select" id="sudah-limit">
          <option value="500" selected>Tampilkan 500 baris</option>
          <option value="1000">Tampilkan 1000 baris</option>
          <option value="2000">Tampilkan 2000 baris</option>
          <option value="all">Tampilkan semua</option>
        </select>
      </div>
      <div class="table-wrap"><table class="data-table" id="table-sudah">
        <thead><tr>
          <th>No.</th><th>NIP</th><th>Nama</th><th>Jabatan</th><th>Satuan Kerja</th><th>Golongan</th><th>Diklat Diikuti</th><th>Sertifikat Lengkap</th><th>Kelompok</th>
        </tr></thead>
        <tbody></tbody>
      </table></div>
      <div class="table-note" id="sudah-count"></div>
    </section>

    <!-- ============ BELUM PELATIHAN ============ -->
    <section class="page" id="page-belum">
      <div class="page-head">
        <h2>ASN Belum Mengikuti Pelatihan</h2>
        <p>Pegawai yang belum tercatat pernah mengikuti diklat/kursus apapun — prioritas untuk diikutsertakan.</p>
      </div>
      <div class="tiles" id="tiles-belum"></div>
      <div class="card" style="margin-bottom:18px;">
        <h3>Sebaran Kelompok ASN Belum Pelatihan</h3>
        <div class="card-sub">Jumlah ASN yang belum pernah diklat, per kelompok</div>
        <div id="chart-belum-kelompok"></div>
      </div>
      <div class="toolbar">
        <input class="filter-input" id="belum-search" placeholder="Cari nama, NIP, jabatan, atau satuan kerja...">
        <select class="filter-select" id="belum-filter-kelompok">
          <option value="">Semua Kelompok</option>
          <option value="TIK">ASN TIK</option>
          <option value="Non TIK">ASN Non TIK</option>
          <option value="Manajerial">ASN Manajerial</option>
        </select>
        <select class="filter-select" id="belum-limit">
          <option value="500" selected>Tampilkan 500 baris</option>
          <option value="1000">Tampilkan 1000 baris</option>
          <option value="2000">Tampilkan 2000 baris</option>
          <option value="all">Tampilkan semua</option>
        </select>
      </div>
      <div class="table-wrap"><table class="data-table" id="table-belum">
        <thead><tr>
          <th>No.</th><th>NIP</th><th>Nama</th><th>Jabatan</th><th>Satuan Kerja</th><th>Golongan</th><th>Kelompok</th>
        </tr></thead>
        <tbody></tbody>
      </table></div>
      <div class="table-note" id="belum-count"></div>
    </section>

    <!-- ============ REKOMENDASI ============ -->
    <section class="page" id="page-rekomendasi">
      <div class="page-head">
        <h2>Rekomendasi Pelatihan</h2>
        <p>Seluruh pegawai ditampilkan lengkap dengan rekomendasi pelatihan TIK sesuai jabatannya. Klik "Pilih Pelatihan" untuk menentukan pelatihan yang mau diikuti.</p>
      </div>
      <div class="card" style="margin-bottom:18px;">
        <h3>Kategori Jabatan</h3>
        <div class="card-sub">Jumlah pegawai per kategori (Sudah TIK / Rekomendasi ke TIK)</div>
        <div id="chart-rekomendasi-bidang"></div>
      </div>
      <div class="toolbar">
        <input class="filter-input" id="rekomendasi-search" placeholder="Cari nama, NIP, jabatan, atau okupasi...">
        <select class="filter-select" id="rekomendasi-filter-bidang">
          <option value="">Semua Kategori</option>
          <option value="tik">Sudah TIK</option>
          <option value="non-tik">Rekomendasi ke TIK</option>
        </select>
      </div>
      <div class="table-wrap"><table class="data-table" id="table-rekomendasi">
        <thead><tr>
          <th>No.</th><th>NIP</th><th>Nama</th><th>Jabatan Saat Ini</th><th>Satuan Kerja</th><th>Kategori</th><th>Area Fungsi</th><th>Kode Ref</th><th>Nama Okupasi</th><th>Riwayat Diklat</th><th>Pelatihan Wajib</th><th>Pelatihan</th>
        </tr></thead>
        <tbody></tbody>
      </table></div>
      <div class="table-note" id="rekomendasi-count"></div>
    </section>

    <!-- ============ PER OPD ============ -->
    <section class="page" id="page-opd">
      <div class="page-head">
        <h2>ASN per OPD</h2>
        <p>Rekapitulasi jumlah &amp; komposisi pegawai per organisasi perangkat daerah.</p>
      </div>
      <div class="card" style="margin-bottom:18px;">
        <h3>10 OPD dengan ASN Terbanyak</h3>
        <div class="card-sub">Jumlah pegawai per organisasi perangkat daerah</div>
        <div id="chart-opd-top-page"></div>
      </div>
      <div class="toolbar">
        <input class="filter-input" id="opd-search" placeholder="Cari nama OPD...">
      </div>
      <div class="table-wrap"><table class="data-table" id="table-opd">
        <thead><tr>
          <th>No.</th><th>OPD</th><th>Total ASN</th><th>TIK</th><th>Non TIK</th><th>Manajerial</th><th>Belum Diklat</th>
        </tr></thead>
        <tbody></tbody>
      </table></div>
      <div class="table-note" id="opd-count"></div>
    </section>

    <!-- ============ PER GOLONGAN ============ -->
    <section class="page" id="page-golongan">
      <div class="page-head">
        <h2>ASN per Golongan</h2>
        <p>Rekapitulasi jumlah pegawai per golongan ruang.</p>
      </div>
      <div id="chart-golongan-full" class="card" style="margin-bottom:18px;"></div>
      <div class="table-wrap"><table class="data-table" id="table-golongan">
        <thead><tr>
          <th>No.</th><th>Golongan</th><th>Total ASN</th><th>TIK</th><th>Non TIK</th><th>Manajerial</th><th>Rata-rata JP Diklat</th>
        </tr></thead>
        <tbody></tbody>
      </table></div>
    </section>

  </main>
</div>

<div class="modal-backdrop" id="profile-modal">
  <div class="modal modal-wide" id="profile-modal-body"></div>
</div>

<div class="modal-backdrop" id="cert-modal">
  <div class="modal modal-wide" id="cert-modal-body"></div>
</div>

<div class="modal-backdrop" id="upload-modal">
  <div class="modal">
    <h3>Unggah Sertifikat</h3>
    <div class="sub" id="upload-modal-sub">Lengkapi berkas sertifikat untuk riwayat diklat ini.</div>
    <label>Nomor Sertifikat</label>
    <input type="text" id="upload-no-sertifikat" placeholder="Contoh: 800/1234/429.204/2026">
    <label>Berkas Sertifikat (PDF/JPG)</label>
    <input type="file" id="upload-file">
    <div class="modal-actions">
      <button class="btn" id="upload-cancel">Batal</button>
      <button class="btn primary" id="upload-submit">Simpan</button>
    </div>
  </div>
</div>

<div class="modal-backdrop" id="pelatihan-modal">
  <div class="modal modal-wide" id="pelatihan-modal-body"></div>
</div>

<div class="modal-backdrop" id="logout-modal">
  <div class="modal">
    <h3>Keluar dari akun?</h3>
    <div class="sub">Anda perlu masuk lagi untuk mengakses dashboard ini.</div>
    <div class="modal-actions">
      <button type="button" class="btn" id="logout-cancel">Batal</button>
      <button type="button" class="btn primary" id="logout-confirm" style="background:var(--status-critical);border-color:var(--status-critical);">Ya, Keluar</button>
    </div>
  </div>
</div>
<div class="toast" id="toast"></div>


<script id="dashboard-data" type="application/json">{!! $dataJson !!}</script>
<script src="{{ asset('js/dashboard.js') }}"></script>
</body>
</html>
