<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard Bangkom ASN — Kabupaten Banyuwangi</title>
<link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">
</head>
<body>

<button class="hamburger-btn" id="hamburgerBtn" aria-label="Buka menu">
  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="4" y1="7" x2="20" y2="7"/><line x1="4" y1="12" x2="20" y2="12"/><line x1="4" y1="17" x2="20" y2="17"/></svg>
</button>
<div class="sidebar-backdrop" id="sidebarBackdrop"></div>

<div class="app">

  <aside class="sidebar" id="sidebar">
    <div class="brand">
      <div class="brand-mark">BW</div>
      <div class="brand-text">
        <div class="t1">Bangkom ASN</div>
        <div class="t2">Kab. Banyuwangi</div>
      </div>
    </div>

    <div class="nav-group">
      <div class="nav-item active" data-page="ringkasan">Ringkasan</div>

      <div class="nav-item" data-toggle-submenu="profil">Profil Pegawai</div>
      <div class="submenu" id="submenu-profil">
        <div class="nav-item" data-page="profil" data-kelompok="TIK">ASN TIK</div>
        <div class="nav-item" data-page="profil" data-kelompok="Non TIK">ASN Non TIK</div>
        <div class="nav-item" data-page="profil" data-kelompok="Manajerial">ASN Manajerial</div>
      </div>

      <div class="nav-item" data-page="sertifikat">Upload Sertifikat <span class="badge" id="badge-sertifikat"></span></div>
      <div class="nav-item" data-page="bersertifikat">Sudah Bersertifikat <span class="badge" id="badge-bersertifikat"></span></div>
      <div class="nav-item" data-page="riwayat">Riwayat Kursus</div>
      <div class="nav-item" data-page="caridiklat">Cari Diklat</div>
      <div class="nav-item" data-page="sudah">Sudah Pelatihan <span class="badge" id="badge-sudah"></span></div>
      <div class="nav-item" data-page="belum">Belum Pelatihan <span class="badge" id="badge-belum"></span></div>
      <div class="nav-item" data-page="rekomendasi">Rekomendasi Pelatihan <span class="badge" id="badge-rekomendasi"></span></div>

      <div class="nav-sep"></div>
      <div class="nav-label">Rekapitulasi</div>
      <div class="nav-item" data-page="opd">ASN per OPD</div>
      <div class="nav-item" data-page="golongan">ASN per Golongan</div>
    </div>
  </aside>

  <main class="content">
    <div class="topbar">
      <div>
        <h1 id="topbar-title">Ringkasan</h1>
        <div class="sub" id="topbar-sub">Gambaran umum kondisi SDM &amp; pengembangan kompetensi</div>
      </div>
      <button class="theme-toggle" id="theme-toggle" style="margin-left:auto;">&#9789; Mode</button>
    </div>

    <!-- ============ RINGKASAN ============ -->
    <section class="page active" id="page-ringkasan">
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
        <p>Pegawai dengan <strong>latar belakang gelar bidang tertentu</strong> (IT, kesehatan, hukum, ekonomi, pendidikan, pertanian, dst) namun jabatan saat ini <strong>tidak berkaitan dengan bidang gelarnya</strong> — direkomendasikan mengikuti pelatihan penyegaran kompetensi sesuai bidang keilmuannya.</p>
      </div>
      <div class="card" style="margin-bottom:18px;">
        <h3>Rekomendasi per Bidang Gelar</h3>
        <div class="card-sub">Jumlah pegawai direkomendasikan penyegaran, per bidang keilmuan</div>
        <div id="chart-rekomendasi-bidang"></div>
      </div>
      <div class="toolbar">
        <input class="filter-input" id="rekomendasi-search" placeholder="Cari nama, NIP, atau jabatan...">
        <select class="filter-select" id="rekomendasi-filter-bidang"><option value="">Semua Bidang</option></select>
      </div>
      <div class="table-wrap"><table class="data-table" id="table-rekomendasi">
        <thead><tr>
          <th>No.</th><th>NIP</th><th>Nama</th><th>Gelar</th><th>Bidang Gelar</th><th>Jabatan Saat Ini</th><th>Satuan Kerja</th><th>Rekomendasi</th>
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
          <th>OPD</th><th>Total ASN</th><th>TIK</th><th>Non TIK</th><th>Manajerial</th><th>Belum Diklat</th>
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
          <th>Golongan</th><th>Total ASN</th><th>TIK</th><th>Non TIK</th><th>Manajerial</th><th>Rata-rata JP Diklat</th>
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
<div class="toast" id="toast"></div>


<script id="dashboard-data" type="application/json">{!! $dataJson !!}</script>
<script src="{{ asset('js/dashboard.js') }}"></script>
</body>
</html>
