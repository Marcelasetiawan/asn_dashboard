<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard Bangkom ASN — Pemkab Banyuwangi</title>
<link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">
</head>
<body>

<button class="hamburger-btn" id="hamburgerBtn" aria-label="Buka menu">
  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="4" y1="7" x2="20" y2="7"/><line x1="4" y1="12" x2="20" y2="12"/><line x1="4" y1="17" x2="20" y2="17"/></svg>
</button>
<div class="sidebar-backdrop" id="sidebarBackdrop"></div>

<div class="app">
  <div class="sidebar" id="sidebarEl">
    <div class="brand">
      <div class="eyebrow">Pemkab Banyuwangi</div>
      <h1>Dashboard Bangkom ASN</h1>
    </div>
    <div class="nav-item active" data-page="ringkasan">Ringkasan</div>
    <div class="nav-item" data-page="diklat-unit">Diklat &amp; Unit Kerja</div>
    <div class="nav-item" data-page="analisis-lanjutan">Analisis Lanjutan</div>
    <div class="nav-item" data-page="prediksi">Prediksi</div>
    <div class="sidebar-foot">Data: 1.000 ASN &middot; Standar Bangkom 20 JP/tahun</div>
  </div>

  <div class="main">

    <!-- ══════════════ HALAMAN 1: RINGKASAN ══════════════ -->
    <div class="page active" id="page-ringkasan">
      <div class="page-head">
        <h2>Ringkasan Pengembangan Kompetensi</h2>
        <p>Gambaran umum bangkom ASN Kabupaten Banyuwangi, dengan acuan standar minimal <b>20 Jam Pelajaran (JP) per tahun</b> sesuai regulasi.</p>
      </div>

      <div class="filter-row">
        <div style="max-width:220px;">
          <label class="f-label">Filter Tahun</label>
          <select id="filterTahun"><option value="">Semua Tahun</option></select>
        </div>
      </div>

      <div class="stat-grid" id="stat-grid"></div>

      <div class="grid-2">
        <div class="card">
          <h3>Top 15 Diklat Paling Banyak Diikuti</h3>
          <div class="sub">Klik batang untuk memfilter halaman "Diklat &amp; Unit Kerja".</div>
          <div id="chartTop"></div>
        </div>
        <div class="card">
          <h3>Jenis Sertifikasi</h3>
          <div class="sub">Klik salah satu bagian untuk memfilter chart Top Diklat di sebelah (sinkron).</div>
          <div id="chartJenis"></div>
        </div>
      </div>

      <div class="card">
        <h3>Rata-rata JP per Tahun</h3>
        <div class="sub">Tren investasi bangkom dari tahun ke tahun (data tergantung kelengkapan pencatatan tiap tahun).</div>
        <div id="chartJpTahun"></div>
      </div>
    </div>

    <!-- ══════════════ HALAMAN 2: DIKLAT & UNIT KERJA (GABUNGAN) ══════════════ -->
    <div class="page" id="page-diklat-unit">
      <div class="page-head">
        <h2>Diklat &amp; Unit Kerja</h2>
        <p>Cari diklat dan lihat rekap unit kerja sekaligus — keduanya saling memfilter satu sama lain.</p>
      </div>

      <div class="card">
        <div class="filter-row">
          <div style="flex:2;">
            <label class="f-label">Cari nama diklat</label>
            <input type="text" id="searchDiklat" placeholder="contoh: SPBE, PBJP, Kepemimpinan...">
          </div>
          <div>
            <label class="f-label">Jenis Sertifikasi</label>
            <select id="filterJenis"><option value="">Semua Jenis</option></select>
          </div>
          <div>
            <label class="f-label">Satuan Kerja</label>
            <select id="filterUnitDiklat"><option value="">Semua Unit Kerja</option></select>
          </div>
        </div>
        <div id="hasilFilterInfo" class="note"></div>
      </div>

      <div class="grid-2">
        <div class="card">
          <h3>Daftar Diklat</h3>
          <div class="scroll-y">
            <table><thead><tr><th>Nama Diklat</th><th>Jenis</th><th>Jumlah Peserta</th></tr></thead>
            <tbody id="tblDiklat"></tbody></table>
          </div>
        </div>
        <div class="card">
          <h3>Rekap Unit Kerja (ikut ter-filter)</h3>
          <div class="scroll-y">
            <table><thead><tr><th>Unit Kerja</th><th>Jml Riwayat</th><th>Jml Pegawai Terlibat</th></tr></thead>
            <tbody id="tblUnit"></tbody></table>
          </div>
        </div>
      </div>
    </div>

    <!-- ══════════════ HALAMAN 3: ANALISIS LANJUTAN (DIFOKUSKAN) ══════════════ -->
    <div class="page" id="page-analisis-lanjutan">
      <div class="page-head">
        <h2>Analisis Lanjutan</h2>
        <p>Dua insight paling bermakna dari data: kepatuhan standar JP per unit kerja, dan relevansi nama diklat terhadap instansi/profesi peserta.</p>
      </div>

      <div class="card">
        <h3>Kepatuhan Standar 20 JP/Tahun per Unit Kerja</h3>
        <div class="sub">Unit kerja dengan rata-rata JP terendah &amp; tertinggi (minimal 10 pegawai).</div>
        <div class="grid-2">
          <div>
            <div class="sub" style="font-weight:600;color:var(--warn);">5 Terendah</div>
            <div id="chartJpTerendah"></div>
          </div>
          <div>
            <div class="sub" style="font-weight:600;color:var(--teal);">5 Tertinggi</div>
            <div id="chartJpTertinggi"></div>
          </div>
        </div>
      </div>

      <div class="card">
        <h3>Relevansi Nama Diklat terhadap Instansi/Profesi</h3>
        <div class="sub" id="relevansiSub"></div>
        <div class="grid-2">
          <div id="chartRelevansi"></div>
          <div>
            <div class="sub" style="font-weight:600;">Contoh Diklat Spesifik Domain &amp; Sebaran Unit Kerja Pesertanya</div>
            <div class="scroll-y" style="max-height:280px;">
              <table><thead><tr><th>Nama Diklat</th><th>Jml Peserta</th><th>Unit Kerja Terbanyak</th></tr></thead>
              <tbody id="tblCrossmatch"></tbody></table>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ══════════════ HALAMAN 4: PREDIKSI (DIFOKUSKAN) ══════════════ -->
    <div class="page" id="page-prediksi">
      <div class="page-head">
        <h2>Prediksi Kepatuhan Standar Bangkom</h2>
        <p>Model memprediksi apakah seorang ASN berpotensi memenuhi standar 20 JP/tahun, berdasarkan atribut kepegawaian dasar.</p>
      </div>

      <div class="card">
        <div class="grid-3">
          <div><label class="f-label">Jenis Kelamin</label>
            <select id="predGender"><option value="0">Laki-laki</option><option value="1">Perempuan</option></select></div>
          <div><label class="f-label">Golongan</label>
            <select id="predGolongan">
              <option value="1">II/c</option><option value="2">II/d</option><option value="3">III/a</option>
              <option value="4">III/b</option><option value="5" selected>III/c</option><option value="6">III/d</option>
              <option value="7">IV/a</option><option value="8">IV/b</option><option value="9">IV/c</option><option value="10">IV/d</option>
            </select></div>
          <div><label class="f-label">Pendidikan</label>
            <select id="predPendidikan">
              <option value="1">DI</option><option value="2">DII</option><option value="3">DIII</option>
              <option value="4">DIV</option><option value="5" selected>S1</option><option value="6">S2</option><option value="7">S3</option>
            </select></div>
          <div><label class="f-label">Status Eselon</label>
            <select id="predEselon"><option value="0">Non-Struktural</option><option value="1">Struktural</option></select></div>
          <div><label class="f-label">Masa Kerja (tahun)</label><input type="number" id="predMasaKerja" value="5" min="0" max="40"></div>
          <div><label class="f-label">Sisa Masa Kerja (tahun)</label><input type="number" id="predSisaMasaKerja" value="10" min="0" max="40"></div>
        </div>
        <div style="margin-top:16px;"><button class="btn" onclick="jalankanPrediksi()">Jalankan Prediksi</button></div>
        <div id="predictOutput"></div>
      </div>
    </div>

  </div>
</div>

<div class="overlay" id="overlay"><div class="modal" id="modalContent"></div></div>

<script src="{{ asset('js/chart-functions.js') }}"></script>
<script src="{{ asset('js/dashboard.js') }}"></script>
</body>
</html>
