(function () {
  "use strict";

  var RAW = JSON.parse(document.getElementById("dashboard-data").textContent);
  var PEGAWAI = RAW.pegawai;
  var DIKLAT = RAW.diklat;

  var SERIES = {
    "TIK": "var(--series-1)",
    "Non TIK": "var(--series-2)",
    "Manajerial": "var(--series-3)"
  };
  var PILL = { "TIK": "tik", "Non TIK": "nontik", "Manajerial": "manajerial" };
  // Palet warna bergilir untuk chart batang yang membandingkan BANYAK
  // kategori (mis. per OPD, per golongan) -- supaya tiap batang beda warna
  // (lebih mudah dibedakan sekilas), bukan satu warna flat semua.
  var SERIES_CYCLE = ["var(--series-1)", "var(--series-2)", "var(--series-3)", "var(--series-4)", "var(--series-5)", "var(--series-6)", "var(--series-7)", "var(--series-8)"];
  function seriesColor(i) { return SERIES_CYCLE[i % SERIES_CYCLE.length]; }
  // Kunci "nama_okupasi" tetap dipakai untuk kelompokkan pilihan Pelatihan
  // Wajib (Dasar/Menengah/Tinggi) di tabel pelatihan_dipilih -- beda dari
  // nama_okupasi asli (TIK) supaya tidak saling menimpa waktu disimpan.
  var WAJIB_OKUPASI_KEY = "Pelatihan Wajib";

  // Tabel-tabel besar (bisa ribuan baris) dibatasi 500 baris per default
  // biar render-nya cepat, tapi user bisa ganti sendiri lewat dropdown
  // "Tampilkan" di tiap tabel -- termasuk pilihan "Tampilkan semua".
  var ROW_LIMITS = { profil: 500, bersertifikat: 500, riwayat: 500, sudah: 500, belum: 500 };
  function rowLimitText(shown, total, noun) {
    if (total <= shown) return "Menampilkan semua " + fmtInt(total) + " " + noun;
    return "Menampilkan " + fmtInt(shown) + " dari " + fmtInt(total) + " " + noun;
  }
  function wireRowLimitSelect(key, renderFn) {
    var sel = document.getElementById(key + "-limit");
    if (!sel) return;
    sel.addEventListener("change", function () {
      ROW_LIMITS[key] = sel.value === "all" ? Infinity : parseInt(sel.value, 10);
      renderFn();
    });
  }

  // index diklat per nip
  var DIKLAT_BY_NIP = {};
  DIKLAT.forEach(function (d) {
    (DIKLAT_BY_NIP[d.nip] = DIKLAT_BY_NIP[d.nip] || []).push(d);
  });
  var PEGAWAI_BY_NIP = {};
  PEGAWAI.forEach(function (p) { PEGAWAI_BY_NIP[p.nip] = p; });

  function esc(s) {
    if (s === null || s === undefined) return "";
    return String(s).replace(/[&<>"']/g, function (c) {
      return { "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[c];
    });
  }
  function fmtInt(n) { return Number(n || 0).toLocaleString("id-ID"); }
  function initials(nama) {
    var words = String(nama || "?").replace(/,.*/, "").trim().split(/\s+/);
    return (words[0] || "?").charAt(0).toUpperCase() + (words[1] ? words[1].charAt(0).toUpperCase() : "");
  }
  function nameLink(nip, nama) {
    return '<span class="clickable-name" data-open-profile="' + esc(nip) + '">' + esc(nama || nip) + '</span>';
  }

  // -------------------------------------------------------------------
  // Profil Pegawai -- modal detail (dipakai dari semua tabel: klik nama pegawai)
  // -------------------------------------------------------------------
  function openProfileModal(nip) {
    var p = PEGAWAI_BY_NIP[nip];
    if (!p) return;
    var riwayat = (DIKLAT_BY_NIP[nip] || []).slice().sort(function (a, b) {
      return String(b.pelaksanaan || "").localeCompare(String(a.pelaksanaan || ""));
    });

    var flags = "";
    flags += '<span class="pill ' + PILL[p.kelompok] + '">' + esc(p.kelompok) + '</span>';
    if (p.bidang_gelar) flags += '<span class="pill ' + bidangPillClass(p.bidang_gelar) + '">Gelar ' + esc(p.bidang_gelar) + '</span>';
    if (p.rekomendasi_pelatihan) flags += '<span class="pill crit" title="Direkomendasikan penyegaran bidang ' + esc(p.bidang_gelar) + '">Direkomendasikan Penyegaran</span>';
    var rekomendasiUmumHtml = p.rekomendasi_pelatihan_umum
      ? '<div class="profile-section-title">Rekomendasi Pelatihan</div>' +
        '<div class="profile-diklat-item">' +
        '<div class="dname">' + esc(p.rekomendasi_pelatihan_umum) + '</div>' +
        '<div class="dmeta">Jabatan saat ini (' + esc(p.jabatan || "-") + ') tidak menyinggung bidang gelar ' + esc(p.bidang_gelar) +
        ' -- karena itu, yang WAJIB diikuti bukan pelatihan bidang gelarnya, tapi pelatihan yang sesuai dengan PEKERJAANNYA SEKARANG (lihat daftar Pelatihan Wajib di bawah). Untuk memilih, gunakan tombol "Pelatihan Wajib" di kolom Pelatihan pada tabel Rekomendasi Pelatihan.</div>' +
        '</div>'
      : '';

    var pelatihanWajib = p.pelatihan_wajib || [];
    var pelatihanWajibHtml = '';
    if (pelatihanWajib.length) {
      var urutLevel = { "Dasar": 1, "Menengah": 2, "Tinggi": 3 };
      var terurut = pelatihanWajib.slice().sort(function (a, b) { return (a.urutan_level || urutLevel[a.level] || 99) - (b.urutan_level || urutLevel[b.level] || 99); });
      var judulWajib = p.rekomendasi_pelatihan
        ? 'Pelatihan Wajib Sesuai Pekerjaan Saat Ini'
        : 'Pelatihan Wajib';
      var dipilihWajibNama = (p.pelatihan_dipilih || [])
        .filter(function (d) { return d.nama_okupasi === WAJIB_OKUPASI_KEY; })
        .map(function (d) { return d.nama_pelatihan; });
      pelatihanWajibHtml = '<div class="profile-section-title">' + judulWajib + ' (' + (p.pelatihan_wajib_terpenuhi || 0) + '/' + (p.pelatihan_wajib_total || 0) + ' terpenuhi) &mdash; kategori: ' + esc(p.jabatan || "-") + '</div>' +
        '<div class="sub">Untuk memilih pelatihan yang mau diikuti, gunakan tombol "Pelatihan Wajib" di kolom Pelatihan pada tabel Rekomendasi Pelatihan.</div>' +
        terurut.map(function (w) {
          var status = w.sudah_diikuti ? '<span class="pill good">Sudah</span>' : '<span class="pill warn">Belum</span>';
          var dipilihPill = dipilihWajibNama.indexOf(w.nama_pelatihan) >= 0 ? ' <span class="pill terpilih-info">Dipilih</span>' : '';
          return '<div class="profile-diklat-item">' +
            '<div class="dname"><span class="pill" style="margin-right:6px;">' + esc(w.level) + '</span>' + esc(w.nama_pelatihan) + '</div>' +
            '<div class="dmeta">' + status + dipilihPill + '</div>' +
            '</div>';
        }).join('');
    }
    flags += p.sudah_diklat ? '<span class="pill good">' + p.jumlah_diklat + ' Diklat Diikuti</span>' : '<span class="pill warn">Belum Pernah Diklat</span>';

    var kv = [
      ["NIP", p.nip], ["Jenis Kelamin", p.jenis_kelamin === "L" ? "Laki-laki" : (p.jenis_kelamin === "P" ? "Perempuan" : "-")],
      ["Status Kepegawaian", p.status || "-"], ["Golongan / Ruang", p.golongan_ruang || "-"],
      ["Eselon", p.eselon || "- (Non Struktural)"], ["Jabatan", p.jabatan || "-"],
      ["Satuan Kerja / OPD", p.satuan_kerja || "-"], ["Pendidikan Terakhir", (p.pendidikan || "-") + (p.tahun_lulus ? " (lulus " + p.tahun_lulus + ")" : "")],
      ["Gelar Depan", p.gelar_depan || "-"], ["Gelar Belakang", p.gelar_belakang || "-"],
      ["Total JP Diklat", fmtInt(p.total_jp) + " JP"], ["Sertifikat Belum Lengkap", p.sertifikat_kurang > 0 ? p.sertifikat_kurang + " riwayat" : "Tidak ada"]
    ];
    if (p.jabatan_fungsional_spesifik) kv.push(["Jabatan Fungsional Spesifik", p.jabatan_fungsional_spesifik]);
    if (p.unor_detail) kv.push(["Unit Kerja Detail", p.unor_detail]);
    var kvHtml = kv.map(function (row) {
      return '<div class="kv-item"><div class="kv-label">' + esc(row[0]) + '</div><div class="kv-val">' + esc(row[1]) + '</div></div>';
    }).join("");

    var riwayatHtml;
    if (!riwayat.length) {
      riwayatHtml = '<div class="empty-state" style="padding:24px 10px;"><div class="big">&#128218;</div>Belum ada riwayat pelatihan tercatat untuk pegawai ini.</div>';
    } else {
      riwayatHtml = riwayat.map(function (d) {
        var idx = DIKLAT.indexOf(d);
        var lengkap = d.sertifikat_lengkap || uploadedOverrides[idx];
        var certInfo = certInfoFor(idx);
        return '<div class="profile-diklat-item">' +
          '<div class="dname">' + esc(d.nama_diklat) + '</div>' +
          '<div class="dmeta">' + esc(d.jenis_sertifikasi || "-") + ' &middot; ' + esc(d.penyelenggara || "-") + ' &middot; ' + esc(d.pelaksanaan || "-") +
          ' &middot; ' + (d.jp || 0) + ' JP &middot; ' +
          (lengkap ? '<span class="pill good">Sertifikat Lengkap</span>' : '<span class="pill warn">Sertifikat Belum Lengkap</span>') +
          (certInfo.available ? ' &middot; ' + certLinkHtml(idx) : '') +
          '</div></div>';
      }).join("");
    }

    document.getElementById("profile-modal-body").innerHTML =
      '<div class="profile-head">' +
      '<div class="profile-avatar">' + initials(p.nama) + '</div>' +
      '<div><h3>' + esc(p.nama) + '</h3><div class="role">' + esc(p.jabatan || "-") + ' &middot; ' + esc(p.satuan_kerja || "-") + '</div></div>' +
      '<button class="profile-close" id="profile-modal-close">&times;</button>' +
      '</div>' +
      '<div class="profile-flags">' + flags + '</div>' +
      '<div class="profile-kv">' + kvHtml + '</div>' +
      rekomendasiUmumHtml +
      pelatihanWajibHtml +
      '<div class="profile-section-title">Riwayat Pelatihan Diikuti (' + riwayat.length + ')</div>' +
      riwayatHtml;

    document.getElementById("profile-modal-close").addEventListener("click", closeProfileModal);
    document.getElementById("profile-modal").classList.add("open");
  }
  function closeProfileModal() { document.getElementById("profile-modal").classList.remove("open"); }
  document.getElementById("profile-modal").addEventListener("click", function (e) {
    if (e.target === this) closeProfileModal();
  });
  // Event delegation: tangkap klik pada SEMUA elemen [data-open-profile] di seluruh halaman,
  // termasuk yang dirender belakangan (tabel yang di-render ulang saat filter berubah).
  document.body.addEventListener("click", function (e) {
    if (e.target.closest("[data-view-cert]")) return; // ditangani listener sertifikat sendiri
    var el = e.target.closest("[data-open-profile]");
    if (el) openProfileModal(el.getAttribute("data-open-profile"));
  });

  // -------------------------------------------------------------------
  // Theme toggle
  // -------------------------------------------------------------------
  var themeBtn = document.getElementById("theme-toggle");
  function applyTheme(t) {
    if (t === "dark") document.documentElement.setAttribute("data-theme", "dark");
    else document.documentElement.removeAttribute("data-theme");
  }
  var currentTheme = "light";
  applyTheme(currentTheme);
  themeBtn.addEventListener("click", function () {
    currentTheme = currentTheme === "dark" ? "light" : "dark";
    applyTheme(currentTheme);
  });

  // -------------------------------------------------------------------
  // Tombol lipat/buka sidebar -- persisten di header sidebar (bukan cuma
  // muncul di layar sempit), tinggal toggle class "collapsed" di sidebar.
  // -------------------------------------------------------------------
  var sidebarEl = document.getElementById("sidebar");
  if (sidebarEl) {
    document.querySelectorAll(".sidebar-toggle").forEach(function (btn) {
      btn.addEventListener("click", function () { sidebarEl.classList.toggle("collapsed"); });
    });
  }

  // -------------------------------------------------------------------
  // Konfirmasi sebelum benar-benar logout -- klik "Keluar" (baik yang di
  // sidebar maupun dropdown nama pengguna) tidak langsung submit form,
  // tapi tampilkan modal konfirmasi dulu.
  // -------------------------------------------------------------------
  (function () {
    var logoutModal = document.getElementById("logout-modal");
    var pendingForm = null;
    if (!logoutModal) return;
    document.querySelectorAll("form[data-logout-form]").forEach(function (f) {
      f.addEventListener("submit", function (e) {
        e.preventDefault();
        pendingForm = f;
        logoutModal.classList.add("open");
      });
    });
    document.getElementById("logout-cancel").addEventListener("click", function () {
      logoutModal.classList.remove("open");
    });
    document.getElementById("logout-confirm").addEventListener("click", function () {
      if (pendingForm) pendingForm.submit();
    });
    logoutModal.addEventListener("click", function (e) {
      if (e.target === logoutModal) logoutModal.classList.remove("open");
    });
  })();

  // -------------------------------------------------------------------
  // Navigation
  // -------------------------------------------------------------------
  var pageTitles = {
    ringkasan: ["Dashboard", "Gambaran umum kondisi SDM & pengembangan kompetensi"],
    profil: ["Profil Pegawai", "Data ASN per kelompok"],
    sertifikat: ["Upload Sertifikat", "Riwayat diklat yang sertifikatnya belum lengkap"],
    bersertifikat: ["Sudah Bersertifikat", "ASN yang sudah ikut pelatihan dan punya sertifikat lengkap"],
    riwayat: ["Riwayat Kursus", "Seluruh riwayat pelatihan yang tercatat"],
    caridiklat: ["Cari Diklat", "Cari program diklat & lihat daftar pesertanya"],
    sudah: ["Sudah Pelatihan", "ASN yang sudah pernah mengikuti diklat"],
    belum: ["Belum Pelatihan", "ASN yang belum pernah mengikuti diklat"],
    rekomendasi: ["Rekomendasi Pelatihan", "Okupasi TIK terdekat untuk jabatan seluruh pegawai"],
    opd: ["ASN per OPD", "Rekapitulasi per organisasi perangkat daerah"],
    golongan: ["ASN per Golongan", "Rekapitulasi per golongan ruang"]
  };

  function showPage(page, kelompok) {
    document.querySelectorAll(".page").forEach(function (el) { el.classList.remove("active"); });
    var target = document.getElementById("page-" + page);
    if (target) target.classList.add("active");
    document.querySelectorAll(".nav-item[data-page]").forEach(function (el) {
      el.classList.toggle("active", el.getAttribute("data-page") === page &&
        (!el.getAttribute("data-kelompok") || el.getAttribute("data-kelompok") === kelompok));
    });
    var t = pageTitles[page];
    if (t) {
      document.getElementById("topbar-title").textContent = t[0];
      document.getElementById("topbar-sub").textContent = t[1];
    }
    if (page === "profil" && kelompok) setProfilTab(kelompok);
  }

  document.querySelectorAll(".nav-item[data-page]").forEach(function (el) {
    el.addEventListener("click", function () {
      showPage(el.getAttribute("data-page"), el.getAttribute("data-kelompok"));
      if (typeof closeSidebar === "function") closeSidebar();
    });
  });
  document.querySelectorAll("[data-toggle-submenu]").forEach(function (el) {
    el.addEventListener("click", function () {
      var id = "submenu-" + el.getAttribute("data-toggle-submenu");
      document.getElementById(id).classList.toggle("open");
    });
  });
  document.getElementById("submenu-profil").classList.add("open");

  // -------------------------------------------------------------------
  // Global search -> jumps to Profil Pegawai filtered
  // -------------------------------------------------------------------
  // -------------------------------------------------------------------
  // Toast
  // -------------------------------------------------------------------
  var toastTimer;
  function toast(msg) {
    var el = document.getElementById("toast");
    el.textContent = msg;
    el.classList.add("show");
    clearTimeout(toastTimer);
    toastTimer = setTimeout(function () { el.classList.remove("show"); }, 3200);
  }

  // -------------------------------------------------------------------
  // Bar chart helper (simple horizontal bars, follows dataviz mark spec:
  // thin marks, rounded ends, legend, direct value labels)
  // -------------------------------------------------------------------
  function renderHBars(container, rows, opts) {
    opts = opts || {};
    var max = Math.max.apply(null, rows.map(function (r) { return r.value; })) || 1;
    var html = "";
    rows.forEach(function (r) {
      var pct = Math.max((r.value / max) * 100, 2);
      html += '<div class="hbar-row">' +
        '<div class="hbar-label" title="' + esc(r.label) + '">' + esc(r.label) + '</div>' +
        '<div class="hbar-track"><div class="hbar-fill" style="width:' + pct + '%;background:' + (r.color || "var(--series-1)") + '"></div></div>' +
        '<div class="hbar-val">' + fmtInt(r.value) + '</div>' +
        '</div>';
    });
    container.innerHTML = html;
  }

  function renderStackedLegend(container, items) {
    var html = '<div class="legend">';
    items.forEach(function (it) {
      html += '<span class="sw"><span class="dot" style="background:' + it.color + '"></span>' + esc(it.label) + '</span>';
    });
    html += "</div>";
    container.insertAdjacentHTML("afterbegin", html);
  }

  // -------------------------------------------------------------------
  // Pie / donut chart (SVG ring built from stroke-dasharray segments).
  // Only used for genuine part-to-whole data with a small (<=6) category
  // count -- magnitude comparisons / long tails stay as hbar charts.
  // -------------------------------------------------------------------
  function renderPieChart(container, rows, opts) {
    opts = opts || {};
    var total = rows.reduce(function (s, r) { return s + (r.value || 0); }, 0);
    if (!total) {
      container.innerHTML = '<div class="mini-empty">Tidak ada data.</div>';
      return;
    }
    var size = opts.size || 176;
    var strokeWidth = opts.strokeWidth || 26;
    var radius = (size - strokeWidth) / 2;
    var circumference = 2 * Math.PI * radius;
    var cx = size / 2, cy = size / 2;
    var runningOffset = 0;
    var svgSegs = "";
    rows.forEach(function (r) {
      var pct = (r.value || 0) / total;
      var dash = pct * circumference;
      var color = r.color || "var(--series-1)";
      svgSegs += '<circle cx="' + cx + '" cy="' + cy + '" r="' + radius + '" fill="none" stroke="' + color + '" ' +
        'stroke-width="' + strokeWidth + '" stroke-dasharray="' + dash + ' ' + (circumference - dash) + '" ' +
        'stroke-dashoffset="' + (-runningOffset) + '">' +
        '<title>' + esc(r.label) + ': ' + fmtInt(r.value) + ' (' + (Math.round(pct * 1000) / 10) + '%)</title>' +
        '</circle>';
      runningOffset += dash;
    });
    var svg = '<svg width="' + size + '" height="' + size + '" viewBox="0 0 ' + size + ' ' + size + '" ' +
      'style="transform:rotate(-90deg);">' + svgSegs + '</svg>';
    var center = '<div class="pie-center"><div class="pie-center-value">' + fmtInt(total) + '</div>' +
      '<div class="pie-center-label">' + esc(opts.centerLabel || "Total") + '</div></div>';
    var legend = '<div class="pie-legend">' + rows.map(function (r) {
      var pct = Math.round((r.value || 0) / total * 1000) / 10;
      return '<div class="pie-legend-row"><span class="dot" style="background:' + (r.color || "var(--series-1)") + '"></span>' +
        '<span class="pie-legend-label">' + esc(r.label) + '</span>' +
        '<span class="pie-legend-val">' + fmtInt(r.value) + ' &middot; ' + pct + '%</span></div>';
    }).join("") + '</div>';
    container.innerHTML = '<div class="pie-wrap"><div class="pie-svg-holder">' + svg + center + '</div>' + legend + '</div>';
  }

  // Ikon badge berwarna di pojok kanan atas tiap tile statistik (path SVG
  // gaya Feather icons, stroke putih di atas lingkaran warna --color--).
  var TILE_ICONS = {
    users: '<circle cx="9" cy="7" r="4"/><path d="M1 21v-2a4 4 0 0 1 4-4h4a4 4 0 0 1 4 4v2"/><path d="M17 3.13a4 4 0 0 1 0 7.75"/><path d="M23 21v-2a4 4 0 0 0-3-3.85"/>',
    monitor: '<rect x="2" y="3" width="20" height="14" rx="2" ry="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/>',
    briefcase: '<rect x="2" y="7" width="20" height="14" rx="2" ry="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/>',
    grid: '<rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/>',
    check: '<polyline points="20 6 9 17 4 12"/>',
    alert: '<circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>',
    award: '<circle cx="12" cy="8" r="7"/><polyline points="8.21 13.89 7 23 12 20 17 23 15.79 13.88"/>',
    file: '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/>'
  };
  function tileIconBadge(icon, color) {
    if (!icon || !TILE_ICONS[icon]) return "";
    return '<div class="tile-icon" style="background:' + (color || "var(--series-1)") + '">' +
      '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' +
      TILE_ICONS[icon] + '</svg></div>';
  }

  // =====================================================================
  // RINGKASAN
  // =====================================================================
  function renderRingkasan() {
    var total = PEGAWAI.length;
    var byKelompok = { "TIK": 0, "Non TIK": 0, "Manajerial": 0 };
    var sudahDiklat = 0, belumDiklat = 0, rekomendasi = 0;
    PEGAWAI.forEach(function (p) {
      byKelompok[p.kelompok] = (byKelompok[p.kelompok] || 0) + 1;
      if (p.sudah_diklat) sudahDiklat++; else belumDiklat++;
      if (p.rekomendasi_pelatihan) rekomendasi++;
    });
    var sertifikatKurang = DIKLAT.filter(function (d) { return !d.sertifikat_lengkap; }).length;

    var tiles = [
      { label: "Total ASN Terdata", value: total, sub: "seluruh pegawai", color: "var(--series-7)", icon: "users" },
      { label: "ASN TIK", value: byKelompok["TIK"], sub: (Math.round(byKelompok["TIK"] / total * 1000) / 10) + "% dari total", color: "var(--series-1)", icon: "monitor" },
      { label: "ASN Non TIK", value: byKelompok["Non TIK"], sub: (Math.round(byKelompok["Non TIK"] / total * 1000) / 10) + "% dari total", color: "var(--series-2)", icon: "briefcase" },
      { label: "ASN Manajerial", value: byKelompok["Manajerial"], sub: (Math.round(byKelompok["Manajerial"] / total * 1000) / 10) + "% dari total", color: "var(--series-3)", icon: "grid" },
      { label: "Sudah Ikut Diklat", value: sudahDiklat, sub: (Math.round(sudahDiklat / total * 1000) / 10) + "% dari total", color: "var(--status-good)", icon: "check" },
      { label: "Belum Ikut Diklat", value: belumDiklat, sub: "perlu prioritas", color: "var(--status-serious)", icon: "alert" },
      { label: "Rekomendasi Pelatihan", value: rekomendasi, sub: "gelar tak sesuai jabatan (semua bidang)", color: "var(--series-5)", icon: "award" },
      { label: "Sertifikat Belum Lengkap", value: sertifikatKurang, sub: "dari " + DIKLAT.length + " riwayat diklat", color: "var(--status-warning)", icon: "file" }
    ];
    var html = "";
    tiles.forEach(function (t) {
      var pct = Math.min(100, Math.round((t.value / total) * 100));
      html += '<div class="tile">' + tileIconBadge(t.icon, t.color) +
        '<div class="label">' + esc(t.label) + '</div>' +
        '<div class="value">' + fmtInt(t.value) + '</div>' +
        '<div class="delta">' + esc(t.sub) + '</div>' +
        '<div class="bar-mini"><div style="width:' + pct + '%;background:' + (t.color || "var(--series-1)") + '"></div></div>' +
        '</div>';
    });
    document.getElementById("tiles-ringkasan").innerHTML = html;

    // Kelompok chart (pie -- 3 kategori, part-to-whole)
    renderPieChart(document.getElementById("chart-kelompok"), [
      { label: "TIK", value: byKelompok["TIK"], color: "var(--series-1)" },
      { label: "Non TIK", value: byKelompok["Non TIK"], color: "var(--series-2)" },
      { label: "Manajerial", value: byKelompok["Manajerial"], color: "var(--series-3)" }
    ], { centerLabel: "ASN" });

    // Top OPD chart
    var byOpd = {};
    PEGAWAI.forEach(function (p) {
      var k = p.satuan_kerja || "Tidak Diketahui";
      byOpd[k] = (byOpd[k] || 0) + 1;
    });
    var topOpd = Object.keys(byOpd).map(function (k) { return { label: k, value: byOpd[k] }; })
      .sort(function (a, b) { return b.value - a.value; }).slice(0, 10);
    renderHBars(document.getElementById("chart-opd-top"), topOpd.map(function (r, i) {
      return { label: r.label, value: r.value, color: seriesColor(i) };
    }));

    // Golongan chart
    var byGol = {};
    PEGAWAI.forEach(function (p) {
      var k = p.golongan_ruang || "Tidak Diketahui";
      byGol[k] = (byGol[k] || 0) + 1;
    });
    var golOrder = Object.keys(byGol).sort();
    renderHBars(document.getElementById("chart-golongan"), golOrder.map(function (k, i) {
      return { label: k, value: byGol[k], color: seriesColor(i) };
    }));

    // Diklat status (pie -- 2 kategori, part-to-whole)
    var sudahPct = Math.round((sudahDiklat / total) * 1000) / 10;
    renderPieChart(document.getElementById("chart-diklat-status"), [
      { label: "Sudah Diklat", value: sudahDiklat, color: "var(--status-good)" },
      { label: "Belum Diklat", value: belumDiklat, color: "var(--status-serious)" }
    ], { centerLabel: sudahPct + "% Sudah" });
  }

  // =====================================================================
  // PROFIL PEGAWAI
  // =====================================================================
  var currentProfilTab = "TIK";
  function renderProfilChart() {
    var members = PEGAWAI.filter(function (p) { return p.kelompok === currentProfilTab; });
    var byGol = {};
    members.forEach(function (p) {
      var k = p.golongan_ruang || "Tidak Diketahui";
      byGol[k] = (byGol[k] || 0) + 1;
    });
    var rows = Object.keys(byGol).sort().map(function (k, i) {
      return { label: k, value: byGol[k], color: seriesColor(i) };
    });
    document.getElementById("profil-chart-sub").textContent =
      "Jumlah pegawai ASN " + currentProfilTab + " (" + members.length + " orang) per golongan ruang";
    if (!rows.length) {
      document.getElementById("chart-profil-golongan").innerHTML = '<div class="mini-empty">Tidak ada data golongan.</div>';
      return;
    }
    renderHBars(document.getElementById("chart-profil-golongan"), rows);
  }
  function setProfilTab(k) {
    var changed = k !== currentProfilTab;
    currentProfilTab = k;
    document.querySelectorAll("#profil-tabs .tab-btn").forEach(function (b) {
      b.classList.toggle("active", b.getAttribute("data-k") === k);
    });
    if (changed) {
      // Reset pencarian & filter saat pindah kelompok, supaya tidak "kebawa"
      // dari kelompok sebelumnya dan bikin tabel baru terlihat kosong/salah.
      document.getElementById("profil-search").value = "";
      document.getElementById("profil-filter-opd").value = "";
      document.getElementById("profil-filter-golongan").value = "";
    }
    renderProfilTable();
    renderProfilChart();
  }
  function buildProfilTabs() {
    var counts = { "TIK": 0, "Non TIK": 0, "Manajerial": 0 };
    PEGAWAI.forEach(function (p) { counts[p.kelompok]++; });
    var order = ["TIK", "Non TIK", "Manajerial"];
    var html = "";
    order.forEach(function (k) {
      html += '<div class="tab-btn' + (k === currentProfilTab ? " active" : "") + '" data-k="' + k + '">' +
        esc(k) + ' <span class="cnt">(' + counts[k] + ')</span></div>';
    });
    document.getElementById("profil-tabs").innerHTML = html;
    document.querySelectorAll("#profil-tabs .tab-btn").forEach(function (b) {
      b.addEventListener("click", function () { setProfilTab(b.getAttribute("data-k")); });
    });
  }
  function fillSelectOptions(selectEl, values, placeholder) {
    var opts = '<option value="">' + placeholder + '</option>';
    values.forEach(function (v) { opts += '<option value="' + esc(v) + '">' + esc(v) + '</option>'; });
    selectEl.innerHTML = opts;
  }
  function renderProfilTable() {
    var q = (document.getElementById("profil-search").value || "").toLowerCase().trim();
    var opd = document.getElementById("profil-filter-opd").value;
    var gol = document.getElementById("profil-filter-golongan").value;
    var rows = PEGAWAI.filter(function (p) { return p.kelompok === currentProfilTab; });
    if (opd) rows = rows.filter(function (p) { return p.satuan_kerja === opd; });
    if (gol) rows = rows.filter(function (p) { return p.golongan_ruang === gol; });
    if (q) rows = rows.filter(function (p) {
      return (p.nama || "").toLowerCase().indexOf(q) >= 0 ||
        (p.nip || "").toLowerCase().indexOf(q) >= 0 ||
        (p.jabatan || "").toLowerCase().indexOf(q) >= 0 ||
        (p.satuan_kerja || "").toLowerCase().indexOf(q) >= 0;
    });
    var tbody = document.querySelector("#table-profil tbody");
    if (!rows.length) {
      tbody.innerHTML = '<tr><td colspan="8"><div class="empty-state"><div class="big">&#128269;</div>Tidak ada pegawai yang cocok dengan pencarian/filter.</div></td></tr>';
    } else {
      tbody.innerHTML = rows.slice(0, ROW_LIMITS.profil).map(function (p, i) {
        return '<tr>' +
          '<td>' + (i + 1) + '</td>' +
          '<td>' + esc(p.nip) + '</td>' +
          '<td class="strong">' + nameLink(p.nip, p.nama) + '</td>' +
          '<td>' + esc(p.jabatan || "-") + '</td>' +
          '<td>' + esc(p.satuan_kerja || "-") + '</td>' +
          '<td>' + esc(p.golongan_ruang || "-") + '</td>' +
          '<td>' + (p.sudah_diklat ? '<span class="pill good">' + p.jumlah_diklat + ' diklat</span>' : '<span class="pill warn">Belum</span>') + '</td>' +
          '<td><span class="pill ' + PILL[p.kelompok] + '">' + esc(p.kelompok) + '</span></td>' +
          '</tr>';
      }).join("");
    }
    document.getElementById("profil-count").textContent = rowLimitText(Math.min(rows.length, ROW_LIMITS.profil), rows.length, "pegawai");
  }
  function initProfilPage() {
    buildProfilTabs();
    renderProfilChart();
    var opds = Array.from(new Set(PEGAWAI.map(function (p) { return p.satuan_kerja; }).filter(Boolean))).sort();
    var gols = Array.from(new Set(PEGAWAI.map(function (p) { return p.golongan_ruang; }).filter(Boolean))).sort();
    fillSelectOptions(document.getElementById("profil-filter-opd"), opds, "Semua OPD");
    fillSelectOptions(document.getElementById("profil-filter-golongan"), gols, "Semua Golongan");
    ["profil-search"].forEach(function (id) { document.getElementById(id).addEventListener("input", renderProfilTable); });
    ["profil-filter-opd", "profil-filter-golongan"].forEach(function (id) { document.getElementById(id).addEventListener("change", renderProfilTable); });
    wireRowLimitSelect("profil", renderProfilTable);
    renderProfilTable();
  }

  // =====================================================================
  // UPLOAD SERTIFIKAT  (in-memory only -- no backend/persistence in prototype)
  // =====================================================================
  var uploadedOverrides = {}; // key: diklat index -> true once "uploaded" in this session
  function sertifikatRows() {
    return DIKLAT.map(function (d, idx) { return { d: d, idx: idx }; })
      .filter(function (r) { return !r.d.sertifikat_lengkap && !uploadedOverrides[r.idx]; });
  }
  function renderSertifikatTiles() {
    var kurang = sertifikatRows().length;
    var total = DIKLAT.length;
    document.getElementById("tiles-sertifikat").innerHTML =
      '<div class="tile"><div class="label">Belum Lengkap</div><div class="value">' + fmtInt(kurang) + '</div><div class="delta warn">dari ' + fmtInt(total) + ' riwayat diklat</div></div>' +
      '<div class="tile"><div class="label">Sudah Lengkap</div><div class="value">' + fmtInt(total - kurang) + '</div><div class="delta">' + Math.round((total - kurang) / total * 100) + '% dari total</div></div>' +
      '<div class="tile"><div class="label">Total Riwayat Diklat</div><div class="value">' + fmtInt(total) + '</div><div class="delta">seluruh catatan</div></div>';
  }
  function renderSertifikatChart() {
    var byKelompok = { "TIK": 0, "Non TIK": 0, "Manajerial": 0 };
    sertifikatRows().forEach(function (r) {
      var p = PEGAWAI_BY_NIP[r.d.nip];
      if (p) byKelompok[p.kelompok] = (byKelompok[p.kelompok] || 0) + 1;
    });
    renderPieChart(document.getElementById("chart-sertifikat-kelompok"), [
      { label: "TIK", value: byKelompok["TIK"], color: SERIES["TIK"] },
      { label: "Non TIK", value: byKelompok["Non TIK"], color: SERIES["Non TIK"] },
      { label: "Manajerial", value: byKelompok["Manajerial"], color: SERIES["Manajerial"] }
    ], { centerLabel: "Belum Lengkap" });
  }
  function renderSertifikatTable() {
    var q = (document.getElementById("sertifikat-search").value || "").toLowerCase().trim();
    var rows = sertifikatRows();
    if (q) rows = rows.filter(function (r) {
      var p = PEGAWAI_BY_NIP[r.d.nip] || {};
      return (p.nama || "").toLowerCase().indexOf(q) >= 0 || (r.d.nama_diklat || "").toLowerCase().indexOf(q) >= 0;
    });
    var tbody = document.querySelector("#table-sertifikat tbody");
    if (!rows.length) {
      tbody.innerHTML = '<tr><td colspan="8"><div class="empty-state"><div class="big">&#9989;</div>Semua sertifikat sudah lengkap.</div></td></tr>';
    } else {
      tbody.innerHTML = rows.map(function (r, i) {
        var p = PEGAWAI_BY_NIP[r.d.nip] || {};
        return '<tr>' +
          '<td>' + (i + 1) + '</td>' +
          '<td class="strong">' + nameLink(r.d.nip, p.nama || r.d.nip) + '</td>' +
          '<td>' + esc(p.satuan_kerja || "-") + '</td>' +
          '<td>' + esc(r.d.nama_diklat) + '</td>' +
          '<td>' + esc(r.d.penyelenggara || "-") + '</td>' +
          '<td>' + esc(r.d.pelaksanaan || "-") + '</td>' +
          '<td><span class="pill warn">Belum Lengkap</span></td>' +
          '<td><button class="btn small primary" data-upload-idx="' + r.idx + '">Unggah</button></td>' +
          '</tr>';
      }).join("");
      tbody.querySelectorAll("[data-upload-idx]").forEach(function (btn) {
        btn.addEventListener("click", function () { openUploadModal(Number(btn.getAttribute("data-upload-idx"))); });
      });
    }
    document.getElementById("sertifikat-count").textContent = rows.length + " riwayat diklat belum memiliki sertifikat lengkap.";
  }
  var uploadTargetIdx = null;
  var uploadedFiles = {}; // idx -> { dataUrl, mime, fileName }
  var uploadedNoSertifikat = {}; // idx -> string yang diketik user saat unggah
  function openUploadModal(idx) {
    uploadTargetIdx = idx;
    var d = DIKLAT[idx];
    var p = PEGAWAI_BY_NIP[d.nip] || {};
    document.getElementById("upload-modal-sub").textContent = (p.nama || d.nip) + " — " + d.nama_diklat;
    document.getElementById("upload-no-sertifikat").value = "";
    document.getElementById("upload-file").value = "";
    document.getElementById("upload-modal").classList.add("open");
  }
  document.getElementById("upload-cancel").addEventListener("click", function () {
    document.getElementById("upload-modal").classList.remove("open");
  });
  document.getElementById("upload-modal").addEventListener("click", function (e) {
    if (e.target === this) this.classList.remove("open");
  });
  function finishUpload(idx, noSert) {
    uploadedOverrides[idx] = true;
    if (noSert) uploadedNoSertifikat[idx] = noSert;
    document.getElementById("upload-modal").classList.remove("open");
    toast("Sertifikat berhasil diunggah (simulasi — sesi ini saja, belum tersimpan ke server). Bisa dilihat lagi di menu Sudah Bersertifikat / Riwayat Kursus / profil pegawai.");
    renderSertifikatTiles();
    renderSertifikatChart();
    renderSertifikatTable();
    renderBersertifikatTiles();
    renderBersertifikatChart();
    renderBersertifikatTable();
    renderSudahTiles();
    renderSudahChart();
    renderSudahTable();
    if (document.getElementById("page-riwayat").classList.contains("active")) { renderRiwayatChart(); renderRiwayatTable(); }
    if (document.getElementById("page-caridiklat").classList.contains("active")) { renderCariDiklatChart(); renderCariDiklatTable(); }
  }
  document.getElementById("upload-submit").addEventListener("click", function () {
    if (uploadTargetIdx === null) return;
    var idx = uploadTargetIdx;
    var noSert = document.getElementById("upload-no-sertifikat").value.trim();
    var fileEl = document.getElementById("upload-file");
    if (!noSert && !fileEl.files.length) {
      toast("Isi nomor sertifikat atau pilih berkas terlebih dahulu.");
      return;
    }
    if (fileEl.files.length) {
      var file = fileEl.files[0];
      var reader = new FileReader();
      reader.onload = function () {
        uploadedFiles[idx] = { dataUrl: reader.result, mime: file.type, fileName: file.name };
        finishUpload(idx, noSert);
      };
      reader.onerror = function () {
        toast("Gagal membaca berkas, tapi status kelengkapan tetap disimpan.");
        finishUpload(idx, noSert);
      };
      reader.readAsDataURL(file);
    } else {
      finishUpload(idx, noSert);
    }
  });

  // =====================================================================
  // LIHAT SERTIFIKAT (viewer untuk berkas yang sudah diunggah di sesi ini,
  // atau info nomor sertifikat untuk data lama yang sudah lengkap)
  // =====================================================================
  function certInfoFor(idx) {
    var d = DIKLAT[idx];
    if (!d) return { available: false };
    if (uploadedFiles[idx]) {
      return {
        available: true, hasFile: true,
        dataUrl: uploadedFiles[idx].dataUrl, mime: uploadedFiles[idx].mime, fileName: uploadedFiles[idx].fileName,
        noSertifikat: uploadedNoSertifikat[idx] || d.no_sertifikat || "-", source: "upload"
      };
    }
    if (d.sertifikat_lengkap && d.no_sertifikat && d.no_sertifikat !== "-") {
      return { available: true, hasFile: false, noSertifikat: d.no_sertifikat, source: "existing" };
    }
    return { available: false };
  }
  function certLinkHtml(idx) {
    var info = certInfoFor(idx);
    if (!info.available) return '<span class="table-note" style="margin:0;">-</span>';
    return '<button type="button" class="link-btn" data-view-cert="' + idx + '">Lihat Sertifikat</button>';
  }
  function openCertModal(idx) {
    var info = certInfoFor(idx);
    var d = DIKLAT[idx];
    var p = PEGAWAI_BY_NIP[d.nip] || {};
    var body = document.getElementById("cert-modal-body");
    var head = '<h3>Sertifikat Diklat</h3>' +
      '<div class="sub">' + esc(p.nama || d.nip) + ' — ' + esc(d.nama_diklat) + '</div>' +
      '<div class="cert-meta"><b>Nomor Sertifikat:</b> ' + esc(info.noSertifikat || "-") +
      '<br><b>Pelaksanaan:</b> ' + esc(d.pelaksanaan || "-") + ' &middot; <b>JP:</b> ' + esc(d.jp || "-") +
      '<br><b>Penyelenggara:</b> ' + esc(d.penyelenggara || "-") + '</div>';
    var preview = "";
    if (info.hasFile) {
      if ((info.mime || "").indexOf("pdf") >= 0) {
        preview = '<div class="cert-preview"><iframe src="' + info.dataUrl + '"></iframe></div>';
      } else if ((info.mime || "").indexOf("image") >= 0) {
        preview = '<div class="cert-preview"><img src="' + info.dataUrl + '" alt="Berkas sertifikat"></div>';
      } else {
        preview = '<div class="cert-empty">Berkas "' + esc(info.fileName || "") + '" berhasil diunggah, namun jenis berkas ini tidak bisa dipratinjau langsung.</div>';
      }
    } else {
      preview = '<div class="cert-empty">Berkas fisik sertifikat ini belum pernah diunggah ke sistem — hanya nomor sertifikatnya yang tercatat dari data awal. Gunakan menu Upload Sertifikat untuk melengkapi berkasnya.</div>';
    }
    body.innerHTML = head + preview + '<div class="modal-actions"><button class="btn" id="cert-close-btn">Tutup</button></div>';
    document.getElementById("cert-close-btn").addEventListener("click", function () {
      document.getElementById("cert-modal").classList.remove("open");
    });
    document.getElementById("cert-modal").classList.add("open");
  }
  document.getElementById("cert-modal").addEventListener("click", function (e) {
    if (e.target === this) this.classList.remove("open");
  });
  document.body.addEventListener("click", function (e) {
    var el = e.target.closest("[data-view-cert]");
    if (el) openCertModal(Number(el.getAttribute("data-view-cert")));
  });
  function initSertifikatPage() {
    renderSertifikatTiles();
    renderSertifikatChart();
    renderSertifikatTable();
    document.getElementById("sertifikat-search").addEventListener("input", renderSertifikatTable);
  }

  // =====================================================================
  // SUDAH BERSERTIFIKAT (kebalikan dari "Upload Sertifikat" -- pegawai yang
  // sudah ikut diklat DAN punya minimal satu sertifikat lengkap)
  // =====================================================================
  function bersertifikatRows() {
    // jumlah sertifikat lengkap = jumlah_diklat - sertifikat_kurang (dgn override sesi upload)
    return PEGAWAI.filter(function (p) {
      var lengkapAwal = (p.jumlah_diklat || 0) - (p.sertifikat_kurang || 0);
      var lengkapTambahan = (DIKLAT_BY_NIP[p.nip] || []).filter(function (d) {
        var idx = DIKLAT.indexOf(d);
        return !d.sertifikat_lengkap && uploadedOverrides[idx];
      }).length;
      return (lengkapAwal + lengkapTambahan) > 0;
    }).map(function (p) {
      var lengkapAwal = (p.jumlah_diklat || 0) - (p.sertifikat_kurang || 0);
      var lengkapTambahan = (DIKLAT_BY_NIP[p.nip] || []).filter(function (d) {
        var idx = DIKLAT.indexOf(d);
        return !d.sertifikat_lengkap && uploadedOverrides[idx];
      }).length;
      return { p: p, jumlahLengkap: lengkapAwal + lengkapTambahan };
    });
  }
  function renderBersertifikatTiles() {
    var rows = bersertifikatRows();
    document.getElementById("tiles-bersertifikat").innerHTML =
      '<div class="tile"><div class="label">ASN Sudah Bersertifikat</div><div class="value">' + fmtInt(rows.length) + '</div><div class="delta">dari ' + fmtInt(PEGAWAI.length) + ' total ASN</div></div>' +
      '<div class="tile"><div class="label">Sudah Diklat, Sertifikat Belum Lengkap</div><div class="value">' + fmtInt(PEGAWAI.filter(function (p) { return p.sudah_diklat; }).length - rows.length) + '</div><div class="delta warn">perlu tindak lanjut unggah sertifikat</div></div>' +
      '<div class="tile"><div class="label">Belum Pernah Diklat</div><div class="value">' + fmtInt(PEGAWAI.filter(function (p) { return !p.sudah_diklat; }).length) + '</div><div class="delta">lihat menu Belum Pelatihan</div></div>';
  }
  function renderBersertifikatChart() {
    var rows = bersertifikatRows();
    var byKelompok = { "TIK": 0, "Non TIK": 0, "Manajerial": 0 };
    rows.forEach(function (r) { byKelompok[r.p.kelompok] = (byKelompok[r.p.kelompok] || 0) + 1; });
    renderPieChart(document.getElementById("chart-bersertifikat-kelompok"), [
      { label: "TIK", value: byKelompok["TIK"], color: SERIES["TIK"] },
      { label: "Non TIK", value: byKelompok["Non TIK"], color: SERIES["Non TIK"] },
      { label: "Manajerial", value: byKelompok["Manajerial"], color: SERIES["Manajerial"] }
    ], { centerLabel: "Bersertifikat" });
  }
  function renderBersertifikatTable() {
    var q = (document.getElementById("bersertifikat-search").value || "").toLowerCase().trim();
    var kel = document.getElementById("bersertifikat-filter-kelompok").value;
    var rows = bersertifikatRows();
    if (kel) rows = rows.filter(function (r) { return r.p.kelompok === kel; });
    if (q) rows = rows.filter(function (r) {
      var p = r.p;
      return (p.nama || "").toLowerCase().indexOf(q) >= 0 ||
        (p.nip || "").toLowerCase().indexOf(q) >= 0 ||
        (p.jabatan || "").toLowerCase().indexOf(q) >= 0 ||
        (p.satuan_kerja || "").toLowerCase().indexOf(q) >= 0;
    });
    var tbody = document.querySelector("#table-bersertifikat tbody");
    if (!rows.length) {
      tbody.innerHTML = '<tr><td colspan="8"><div class="empty-state"><div class="big">&#128269;</div>Tidak ada yang cocok.</div></td></tr>';
    } else {
      tbody.innerHTML = rows.slice(0, ROW_LIMITS.bersertifikat).map(function (r, i) {
        var p = r.p;
        return '<tr>' +
          '<td>' + (i + 1) + '</td>' +
          '<td>' + esc(p.nip) + '</td>' +
          '<td class="strong">' + nameLink(p.nip, p.nama) + '</td>' +
          '<td>' + esc(p.jabatan || "-") + '</td>' +
          '<td>' + esc(p.satuan_kerja || "-") + '</td>' +
          '<td><span class="pill good">' + p.jumlah_diklat + ' diklat</span></td>' +
          '<td><span class="pill good">' + r.jumlahLengkap + ' sertifikat</span></td>' +
          '<td><span class="pill ' + PILL[p.kelompok] + '">' + esc(p.kelompok) + '</span></td>' +
          '</tr>';
      }).join("");
    }
    document.getElementById("bersertifikat-count").textContent =
      rowLimitText(Math.min(rows.length, ROW_LIMITS.bersertifikat), rows.length, "pegawai yang sudah bersertifikat");
  }
  function initBersertifikatPage() {
    renderBersertifikatTiles();
    renderBersertifikatChart();
    renderBersertifikatTable();
    document.getElementById("bersertifikat-search").addEventListener("input", renderBersertifikatTable);
    document.getElementById("bersertifikat-filter-kelompok").addEventListener("change", renderBersertifikatTable);
    wireRowLimitSelect("bersertifikat", renderBersertifikatTable);
  }

  // =====================================================================
  // RIWAYAT KURSUS
  // =====================================================================
  function renderRiwayatTable() {
    var q = (document.getElementById("riwayat-search").value || "").toLowerCase().trim();
    var jenis = document.getElementById("riwayat-filter-jenis").value;
    var rows = DIKLAT.map(function (d, idx) { return { d: d, idx: idx }; });
    if (jenis) rows = rows.filter(function (r) { return r.d.jenis_sertifikasi === jenis; });
    if (q) rows = rows.filter(function (r) {
      var p = PEGAWAI_BY_NIP[r.d.nip] || {};
      return (p.nama || "").toLowerCase().indexOf(q) >= 0 ||
        (r.d.nama_diklat || "").toLowerCase().indexOf(q) >= 0 ||
        (r.d.penyelenggara || "").toLowerCase().indexOf(q) >= 0;
    });
    var tbody = document.querySelector("#table-riwayat tbody");
    if (!rows.length) {
      tbody.innerHTML = '<tr><td colspan="9"><div class="empty-state"><div class="big">&#128269;</div>Tidak ada riwayat yang cocok.</div></td></tr>';
    } else {
      // Nama pegawai cuma ditulis SEKALI per pegawai (pakai rowspan), bukan
      // diulang di tiap baris riwayat diklatnya -- baris-baris riwayat DIKLAT
      // (kolom nama pegawai) datanya sudah terurut per nip dari backend, jadi
      // baris-baris milik 1 pegawai yang sama pasti berurutan/tidak diselingi.
      var limited = rows.slice(0, ROW_LIMITS.riwayat);
      var html = "";
      var no = 0;
      var i = 0;
      while (i < limited.length) {
        var nip = limited[i].d.nip;
        var group = [];
        while (i < limited.length && limited[i].d.nip === nip) {
          group.push(limited[i]);
          i++;
        }
        var p = PEGAWAI_BY_NIP[nip] || {};
        no++;
        html += group.map(function (r, gi) {
          var d = r.d, idx = r.idx;
          var lengkap = d.sertifikat_lengkap || uploadedOverrides[idx];
          return '<tr>' +
            (gi === 0 ? '<td rowspan="' + group.length + '">' + no + '</td>' : '') +
            (gi === 0 ? '<td class="strong" rowspan="' + group.length + '">' + nameLink(nip, p.nama || nip) + '</td>' : '') +
            '<td>' + esc(d.jenis_sertifikasi || "-") + '</td>' +
            '<td>' + esc(d.nama_diklat) + '</td>' +
            '<td>' + esc(d.penyelenggara || "-") + '</td>' +
            '<td>' + esc(d.pelaksanaan || "-") + '</td>' +
            '<td>' + esc(d.jp || "-") + '</td>' +
            '<td>' + (lengkap ? '<span class="pill good">Lengkap</span>' : '<span class="pill warn">Belum</span>') + '</td>' +
            '<td>' + certLinkHtml(idx) + '</td>' +
            '</tr>';
        }).join("");
      }
      tbody.innerHTML = html;
    }
    document.getElementById("riwayat-count").textContent =
      rowLimitText(Math.min(rows.length, ROW_LIMITS.riwayat), rows.length, "riwayat diklat");
  }
  function renderRiwayatChart() {
    var byJenis = {};
    DIKLAT.forEach(function (d) {
      var k = d.jenis_sertifikasi || "Tidak Diketahui";
      byJenis[k] = (byJenis[k] || 0) + 1;
    });
    var rows = Object.keys(byJenis).map(function (k, i) {
      return { label: k, value: byJenis[k], color: "var(--series-" + ((i % 8) + 1) + ")" };
    }).sort(function (a, b) { return b.value - a.value; });
    renderPieChart(document.getElementById("chart-riwayat-jenis"), rows, { centerLabel: "Riwayat" });
  }
  function initRiwayatPage() {
    var jenisList = Array.from(new Set(DIKLAT.map(function (d) { return d.jenis_sertifikasi; }).filter(Boolean))).sort();
    fillSelectOptions(document.getElementById("riwayat-filter-jenis"), jenisList, "Semua Jenis");
    document.getElementById("riwayat-search").addEventListener("input", renderRiwayatTable);
    document.getElementById("riwayat-filter-jenis").addEventListener("change", renderRiwayatTable);
    wireRowLimitSelect("riwayat", renderRiwayatTable);
    renderRiwayatChart();
    renderRiwayatTable();
  }

  // =====================================================================
  // CARI DIKLAT (dikelompokkan per nama program, bukan per baris kehadiran --
  // untuk menjawab "diklat X pernah diikuti siapa saja")
  // =====================================================================
  function mostCommon(list) {
    var count = {};
    var best = null, bestN = 0;
    list.forEach(function (v) {
      if (!v) return;
      count[v] = (count[v] || 0) + 1;
      if (count[v] > bestN) { bestN = count[v]; best = v; }
    });
    return best;
  }
  function diklatGroups() {
    var byNama = {};
    DIKLAT.forEach(function (d) {
      var key = d.nama_diklat || "(Tanpa Nama)";
      if (!byNama[key]) byNama[key] = { key: key, jenisList: [], penyelenggaraList: [], records: [] };
      byNama[key].jenisList.push(d.jenis_sertifikasi);
      byNama[key].penyelenggaraList.push(d.penyelenggara);
      byNama[key].records.push(d);
    });
    return Object.keys(byNama).map(function (key) {
      var g = byNama[key];
      var totalJp = g.records.reduce(function (s, d) { return s + (parseFloat(d.jp) || 0); }, 0);
      var lengkap = g.records.filter(function (d) { return d.sertifikat_lengkap || uploadedOverrides[DIKLAT.indexOf(d)]; }).length;
      return {
        key: key,
        jenis: mostCommon(g.jenisList) || "-",
        penyelenggara: mostCommon(g.penyelenggaraList) || "-",
        jumlah: g.records.length,
        totalJp: totalJp,
        lengkap: lengkap,
        records: g.records
      };
    });
  }
  function renderCariDiklatTiles() {
    var groups = diklatGroups();
    document.getElementById("tiles-caridiklat").innerHTML =
      '<div class="tile"><div class="label">Program Diklat Berbeda</div><div class="value">' + fmtInt(groups.length) + '</div><div class="delta">tercatat pernah diselenggarakan</div></div>' +
      '<div class="tile"><div class="label">Total Kehadiran Tercatat</div><div class="value">' + fmtInt(DIKLAT.length) + '</div><div class="delta">seluruh baris riwayat diklat</div></div>' +
      '<div class="tile"><div class="label">Rata-rata Peserta / Program</div><div class="value">' + (groups.length ? Math.round(DIKLAT.length / groups.length * 10) / 10 : 0) + '</div><div class="delta">peserta per program</div></div>';
  }
  function renderMiniDiklatList(container, records, query) {
    var q = (query || "").toLowerCase().trim();
    var rows = records.map(function (d) {
      var p = PEGAWAI_BY_NIP[d.nip] || {};
      return { d: d, p: p, idx: DIKLAT.indexOf(d) };
    }).filter(function (r) {
      if (!q) return true;
      return (r.p.nama || "").toLowerCase().indexOf(q) >= 0 ||
        (r.d.nip || "").toLowerCase().indexOf(q) >= 0 ||
        (r.p.satuan_kerja || "").toLowerCase().indexOf(q) >= 0;
    }).sort(function (a, b) { return String(a.p.nama || a.d.nip).localeCompare(String(b.p.nama || b.d.nip)); });
    if (!rows.length) {
      container.innerHTML = '<div class="mini-empty">Tidak ada nama yang cocok.</div>';
      return;
    }
    container.innerHTML = rows.map(function (r, i) {
      var d = r.d, p = r.p;
      var lengkap = d.sertifikat_lengkap || uploadedOverrides[r.idx];
      return '<div class="mini-name-row" data-open-profile="' + esc(d.nip) + '">' +
        '<span class="num">' + (i + 1) + '</span>' +
        '<span class="n">' + esc(p.nama || d.nip) + '</span>' +
        '<span class="j">' + esc(p.satuan_kerja || "-") + '</span>' +
        '<span class="g">' + esc(d.pelaksanaan || "-") + ' &middot; ' + (lengkap ? "Sertifikat Lengkap" : "Sertifikat Belum") + '</span>' +
        (certInfoFor(r.idx).available ? '<span class="g">' + certLinkHtml(r.idx) + '</span>' : '') +
        '</div>';
    }).join("");
  }
  function diklatEfektifLengkap(d) {
    return d.sertifikat_lengkap || uploadedOverrides[DIKLAT.indexOf(d)];
  }
  var DIKLAT_QUICK_FILTERS = [
    { key: "semua", label: "Semua", test: function () { return true; } },
    { key: "lengkap", label: "Sertifikat Lengkap", test: function (d) { return diklatEfektifLengkap(d); } },
    { key: "belum", label: "Sertifikat Belum", test: function (d) { return !diklatEfektifLengkap(d); } }
  ];
  function attachDiklatGroupExpand(tbody, groupsByKey, colspan) {
    function closeRow(row) {
      row.classList.remove("expanded");
      var next = row.nextElementSibling;
      if (next && next.classList.contains("group-detail-row")) next.remove();
    }
    function openRow(row, key, initialFilter) {
      tbody.querySelectorAll("tr.group-row.expanded").forEach(closeRow);
      row.classList.add("expanded");
      var allRecords = (groupsByKey[key] && groupsByKey[key].records) || [];

      var filterBtns = DIKLAT_QUICK_FILTERS.map(function (f) {
        var n = allRecords.filter(f.test).length;
        return '<button class="tab-btn filter-chip-btn' + (f.key === initialFilter ? " active" : "") + '" data-filter-key="' + f.key + '">' +
          esc(f.label) + ' <span class="cnt">(' + n + ')</span></button>';
      }).join("");

      var tr = document.createElement("tr");
      tr.className = "group-detail-row";
      tr.innerHTML = '<td colspan="' + colspan + '"><div class="detail-wrap">' +
        '<div class="filter-chip-row">' + filterBtns + '</div>' +
        '<input type="text" class="detail-search" placeholder="Cari nama peserta ' + esc(key) + '...">' +
        '<div class="mini-name-list"></div>' +
        '<div class="detail-count"></div>' +
        '</div></td>';
      row.parentNode.insertBefore(tr, row.nextSibling);

      var listEl = tr.querySelector(".mini-name-list");
      var countEl = tr.querySelector(".detail-count");
      var searchEl = tr.querySelector(".detail-search");
      var activeFilter = initialFilter;

      function refresh() {
        var filterDef = DIKLAT_QUICK_FILTERS.filter(function (f) { return f.key === activeFilter; })[0] || DIKLAT_QUICK_FILTERS[0];
        var records = allRecords.filter(filterDef.test);
        renderMiniDiklatList(listEl, records, searchEl.value);
        var shown = listEl.querySelectorAll(".mini-name-row").length;
        countEl.textContent = "Menampilkan " + shown + " dari " + records.length + " peserta (" + filterDef.label + ") pada " + key + ".";
      }
      tr.querySelectorAll(".filter-chip-btn").forEach(function (btn) {
        btn.addEventListener("click", function (e) {
          e.stopPropagation();
          activeFilter = btn.getAttribute("data-filter-key");
          tr.querySelectorAll(".filter-chip-btn").forEach(function (b) { b.classList.toggle("active", b === btn); });
          refresh();
        });
      });
      refresh();
      searchEl.addEventListener("input", refresh);
      searchEl.addEventListener("click", function (e) { e.stopPropagation(); });
    }

    tbody.querySelectorAll("tr.group-row").forEach(function (row) {
      row.addEventListener("click", function (e) {
        if (e.target.closest("[data-open-profile], .detail-search, .filter-chip-btn")) return;
        var key = row.getAttribute("data-group-key");
        if (row.classList.contains("expanded")) { closeRow(row); return; }
        openRow(row, key, "semua");
      });
    });
  }
  function renderCariDiklatTable() {
    var q = (document.getElementById("caridiklat-search").value || "").toLowerCase().trim();
    var jenis = document.getElementById("caridiklat-filter-jenis").value;
    var rows = diklatGroups();
    if (jenis) rows = rows.filter(function (g) { return g.jenis === jenis; });
    if (q) rows = rows.filter(function (g) {
      return g.key.toLowerCase().indexOf(q) >= 0 || (g.penyelenggara || "").toLowerCase().indexOf(q) >= 0;
    });
    rows.sort(function (a, b) { return b.jumlah - a.jumlah; });
    var byKey = {};
    rows.forEach(function (g) { byKey[g.key] = g; });
    var tbody = document.querySelector("#table-caridiklat tbody");
    if (!rows.length) {
      tbody.innerHTML = '<tr><td colspan="6"><div class="empty-state"><div class="big">&#128269;</div>Tidak ada diklat yang cocok.</div></td></tr>';
    } else {
      tbody.innerHTML = rows.map(function (g) {
        return '<tr class="group-row" data-group-key="' + esc(g.key) + '">' +
          '<td class="strong"><span class="chev">&#9656;</span>' + esc(g.key) + '</td>' +
          '<td>' + esc(g.jenis) + '</td>' +
          '<td>' + esc(g.penyelenggara) + '</td>' +
          '<td>' + fmtInt(g.jumlah) + '</td>' +
          '<td>' + fmtInt(g.totalJp) + '</td>' +
          '<td>' + g.lengkap + '/' + g.jumlah + '</td>' +
          '</tr>';
      }).join("");
    }
    attachDiklatGroupExpand(tbody, byKey, 6);
    document.getElementById("caridiklat-count").textContent =
      rows.length + " program diklat ditemukan. Klik baris untuk melihat daftar pesertanya.";
  }
  function renderCariDiklatChart() {
    var top = diklatGroups().sort(function (a, b) { return b.jumlah - a.jumlah; }).slice(0, 10);
    renderHBars(document.getElementById("chart-caridiklat-top"), top.map(function (g, i) {
      return { label: g.key, value: g.jumlah, color: seriesColor(i) };
    }));
  }
  function initCariDiklatPage() {
    var jenisList = Array.from(new Set(DIKLAT.map(function (d) { return d.jenis_sertifikasi; }).filter(Boolean))).sort();
    fillSelectOptions(document.getElementById("caridiklat-filter-jenis"), jenisList, "Semua Jenis");
    renderCariDiklatTiles();
    renderCariDiklatChart();
    renderCariDiklatTable();
    document.getElementById("caridiklat-search").addEventListener("input", renderCariDiklatTable);
    document.getElementById("caridiklat-filter-jenis").addEventListener("change", renderCariDiklatTable);
  }

  // =====================================================================
  // BELUM PELATIHAN
  // =====================================================================
  // =====================================================================
  // SUDAH PELATIHAN (pegawai yang minimal 1x pernah ikut diklat -- terlepas
  // dari status kelengkapan sertifikatnya; kebalikan dari "Belum Pelatihan")
  // =====================================================================
  function renderSudahTiles() {
    var rows = PEGAWAI.filter(function (p) { return p.sudah_diklat; });
    var lengkapSemua = rows.filter(function (p) { return (p.sertifikat_kurang || 0) === 0; }).length;
    document.getElementById("tiles-sudah").innerHTML =
      '<div class="tile"><div class="label">ASN Sudah Pelatihan</div><div class="value">' + fmtInt(rows.length) + '</div><div class="delta">dari ' + fmtInt(PEGAWAI.length) + ' total ASN</div></div>' +
      '<div class="tile"><div class="label">Sertifikat Sudah Lengkap Semua</div><div class="value">' + fmtInt(lengkapSemua) + '</div><div class="delta good">tidak ada tunggakan unggah</div></div>' +
      '<div class="tile"><div class="label">Masih Ada Sertifikat Kurang</div><div class="value">' + fmtInt(rows.length - lengkapSemua) + '</div><div class="delta warn">lihat menu Upload Sertifikat</div></div>';
  }
  function renderSudahChart() {
    var byKelompok = { "TIK": 0, "Non TIK": 0, "Manajerial": 0 };
    PEGAWAI.filter(function (p) { return p.sudah_diklat; }).forEach(function (p) { byKelompok[p.kelompok]++; });
    renderPieChart(document.getElementById("chart-sudah-kelompok"), [
      { label: "TIK", value: byKelompok["TIK"], color: SERIES["TIK"] },
      { label: "Non TIK", value: byKelompok["Non TIK"], color: SERIES["Non TIK"] },
      { label: "Manajerial", value: byKelompok["Manajerial"], color: SERIES["Manajerial"] }
    ], { centerLabel: "Sudah Diklat" });
  }
  function renderSudahTable() {
    var q = (document.getElementById("sudah-search").value || "").toLowerCase().trim();
    var kel = document.getElementById("sudah-filter-kelompok").value;
    var rows = PEGAWAI.filter(function (p) { return p.sudah_diklat; });
    if (kel) rows = rows.filter(function (p) { return p.kelompok === kel; });
    if (q) rows = rows.filter(function (p) {
      return (p.nama || "").toLowerCase().indexOf(q) >= 0 ||
        (p.nip || "").toLowerCase().indexOf(q) >= 0 ||
        (p.jabatan || "").toLowerCase().indexOf(q) >= 0 ||
        (p.satuan_kerja || "").toLowerCase().indexOf(q) >= 0;
    });
    var tbody = document.querySelector("#table-sudah tbody");
    if (!rows.length) {
      tbody.innerHTML = '<tr><td colspan="9"><div class="empty-state"><div class="big">&#128269;</div>Tidak ada yang cocok.</div></td></tr>';
    } else {
      tbody.innerHTML = rows.slice(0, ROW_LIMITS.sudah).map(function (p, i) {
        var kurang = p.sertifikat_kurang || 0;
        var lengkapLabel = kurang > 0
          ? '<span class="pill warn">' + (p.jumlah_diklat - kurang) + '/' + p.jumlah_diklat + '</span>'
          : '<span class="pill good">' + p.jumlah_diklat + '/' + p.jumlah_diklat + '</span>';
        return '<tr>' +
          '<td>' + (i + 1) + '</td>' +
          '<td>' + esc(p.nip) + '</td>' +
          '<td class="strong">' + nameLink(p.nip, p.nama) + '</td>' +
          '<td>' + esc(p.jabatan || "-") + '</td>' +
          '<td>' + esc(p.satuan_kerja || "-") + '</td>' +
          '<td>' + esc(p.golongan_ruang || "-") + '</td>' +
          '<td><span class="pill good">' + p.jumlah_diklat + ' diklat</span></td>' +
          '<td>' + lengkapLabel + '</td>' +
          '<td><span class="pill ' + PILL[p.kelompok] + '">' + esc(p.kelompok) + '</span></td>' +
          '</tr>';
      }).join("");
    }
    document.getElementById("sudah-count").textContent =
      rowLimitText(Math.min(rows.length, ROW_LIMITS.sudah), rows.length, "pegawai yang sudah pernah mengikuti pelatihan");
  }
  function initSudahPage() {
    renderSudahTiles();
    renderSudahChart();
    renderSudahTable();
    document.getElementById("sudah-search").addEventListener("input", renderSudahTable);
    document.getElementById("sudah-filter-kelompok").addEventListener("change", renderSudahTable);
    wireRowLimitSelect("sudah", renderSudahTable);
  }

  function renderBelumTable() {
    var q = (document.getElementById("belum-search").value || "").toLowerCase().trim();
    var kel = document.getElementById("belum-filter-kelompok").value;
    var rows = PEGAWAI.filter(function (p) { return !p.sudah_diklat; });
    if (kel) rows = rows.filter(function (p) { return p.kelompok === kel; });
    if (q) rows = rows.filter(function (p) {
      return (p.nama || "").toLowerCase().indexOf(q) >= 0 ||
        (p.nip || "").toLowerCase().indexOf(q) >= 0 ||
        (p.jabatan || "").toLowerCase().indexOf(q) >= 0 ||
        (p.satuan_kerja || "").toLowerCase().indexOf(q) >= 0;
    });
    var tbody = document.querySelector("#table-belum tbody");
    if (!rows.length) {
      tbody.innerHTML = '<tr><td colspan="7"><div class="empty-state"><div class="big">&#9989;</div>Tidak ada yang cocok — kemungkinan semua sudah mengikuti pelatihan.</div></td></tr>';
    } else {
      tbody.innerHTML = rows.slice(0, ROW_LIMITS.belum).map(function (p, i) {
        return '<tr>' +
          '<td>' + (i + 1) + '</td>' +
          '<td>' + esc(p.nip) + '</td>' +
          '<td class="strong">' + nameLink(p.nip, p.nama) + '</td>' +
          '<td>' + esc(p.jabatan || "-") + '</td>' +
          '<td>' + esc(p.satuan_kerja || "-") + '</td>' +
          '<td>' + esc(p.golongan_ruang || "-") + '</td>' +
          '<td><span class="pill ' + PILL[p.kelompok] + '">' + esc(p.kelompok) + '</span></td>' +
          '</tr>';
      }).join("");
    }
    document.getElementById("belum-count").textContent =
      rowLimitText(Math.min(rows.length, ROW_LIMITS.belum), rows.length, "pegawai yang belum pernah diklat");
  }
  function renderBelumTiles() {
    var belum = PEGAWAI.filter(function (p) { return !p.sudah_diklat; });
    document.getElementById("tiles-belum").innerHTML =
      '<div class="tile"><div class="label">ASN Belum Pelatihan</div><div class="value">' + fmtInt(belum.length) + '</div><div class="delta warn">dari ' + fmtInt(PEGAWAI.length) + ' total ASN</div></div>' +
      '<div class="tile"><div class="label">Persentase Belum Pelatihan</div><div class="value">' + Math.round(belum.length / PEGAWAI.length * 100) + '%</div><div class="delta">dari seluruh ASN</div></div>' +
      '<div class="tile"><div class="label">Sudah Pelatihan</div><div class="value">' + fmtInt(PEGAWAI.length - belum.length) + '</div><div class="delta good">lihat menu Sudah Pelatihan</div></div>';
  }
  function renderBelumChart() {
    var byKelompok = { "TIK": 0, "Non TIK": 0, "Manajerial": 0 };
    PEGAWAI.filter(function (p) { return !p.sudah_diklat; }).forEach(function (p) { byKelompok[p.kelompok]++; });
    renderPieChart(document.getElementById("chart-belum-kelompok"), [
      { label: "TIK", value: byKelompok["TIK"], color: SERIES["TIK"] },
      { label: "Non TIK", value: byKelompok["Non TIK"], color: SERIES["Non TIK"] },
      { label: "Manajerial", value: byKelompok["Manajerial"], color: SERIES["Manajerial"] }
    ], { centerLabel: "Belum Diklat" });
  }
  function initBelumPage() {
    document.getElementById("belum-search").addEventListener("input", renderBelumTable);
    document.getElementById("belum-filter-kelompok").addEventListener("change", renderBelumTable);
    wireRowLimitSelect("belum", renderBelumTable);
    renderBelumTiles();
    renderBelumChart();
    renderBelumTable();
  }

  // =====================================================================
  // REKOMENDASI PELATIHAN
  // =====================================================================
  var BIDANG_PILL_CLASS = {
    "Teknologi Informasi": "tik", "Kesehatan": "good", "Pendidikan": "manajerial",
    "Hukum": "crit", "Ekonomi/Akuntansi": "warn", "Pertanian/Peternakan/Perikanan": "good",
    "Psikologi": "nontik", "Arsitektur/Tata Ruang": "nontik", "Sosial & Komunikasi Publik": "warn",
    "Administrasi Publik": "nontik"
  };
  function bidangPillClass(b) { return BIDANG_PILL_CLASS[b] || "nontik"; }

  function renderRekomendasiTable() {
    var q = (document.getElementById("rekomendasi-search").value || "").toLowerCase().trim();
    var kategori = document.getElementById("rekomendasi-filter-bidang").value;
    // SEMUA pegawai ditampilkan -- bukan cuma yang rekomendasi_pelatihan
    // (gelar tidak sesuai jabatan). Kategori "Sudah TIK" (p.jabatan_it true)
    // vs "Rekomendasi ke TIK" (jabatan bukan TIK, dicarikan okupasi TIK
    // terdekat) cuma soal LABEL & framing -- datanya sama-sama diambil dari
    // p.rekomendasi_pelatihan_opsi yang sudah dihitung untuk semua pegawai.
    var rows = PEGAWAI.slice();
    if (kategori === "tik") rows = rows.filter(function (p) { return p.jabatan_it; });
    else if (kategori === "non-tik") rows = rows.filter(function (p) { return !p.jabatan_it; });
    if (q) rows = rows.filter(function (p) {
      return (p.nama || "").toLowerCase().indexOf(q) >= 0 ||
        (p.nip || "").toLowerCase().indexOf(q) >= 0 ||
        (p.jabatan || "").toLowerCase().indexOf(q) >= 0 ||
        (p.rekomendasi_pelatihan_opsi && (p.rekomendasi_pelatihan_opsi.nama_okupasi || "").toLowerCase().indexOf(q) >= 0);
    });
    var tbody = document.querySelector("#table-rekomendasi tbody");
    if (!rows.length) {
      tbody.innerHTML = '<tr><td colspan="12"><div class="empty-state"><div class="big">&#128269;</div>Tidak ada yang cocok.</div></td></tr>';
    } else {
      tbody.innerHTML = rows.map(function (p, i) {
        var opsi = p.rekomendasi_pelatihan_opsi;
        var dipilih = p.pelatihan_dipilih || [];
        var kategoriCell = p.jabatan_it
          ? '<span class="pill good">Sudah TIK</span>'
          : '<span class="pill warn">Rekomendasi ke TIK</span>';
        var areaCell, kodeCell, namaOkupasiCell, pelatihanCell;
        if (p.jabatan_it && opsi) {
          // Sudah TIK: okupasi ini identitas kerjanya sekarang -- tampilkan
          // Area Fungsi / Kode Ref / Nama Okupasi apa adanya.
          areaCell = esc(opsi.area || "-");
          kodeCell = esc(opsi.kode || "-");
          namaOkupasiCell = esc(opsi.nama_okupasi);
        } else if (p.jabatan_it && !opsi) {
          areaCell = kodeCell = namaOkupasiCell = '<span class="muted-cell">-</span>';
        } else {
          // Rekomendasi ke TIK (jabatan bukan TIK): TIDAK dipetakan ke
          // satu Area Fungsi/Kode Ref/Nama Okupasi tertentu -- okupasi
          // terdekat cuma dipakai di belakang layar untuk menyusun daftar
          // pelatihan rekomendasi, bukan diklaim sebagai okupasi pegawai.
          areaCell = kodeCell = namaOkupasiCell = '<span class="muted-cell" title="Jabatan ini di luar bidang TIK, jadi tidak dipetakan ke okupasi tertentu -- tetap diberi rekomendasi pelatihan TIK terdekat di kolom Pelatihan">-</span>';
        }
        var riwayatPegawai = (DIKLAT_BY_NIP[p.nip] || []);
        var riwayatCell;
        if (riwayatPegawai.length) {
          var contohNama = riwayatPegawai.slice(0, 2).map(function (d) { return esc(d.nama_diklat); }).join(', ');
          var sisa = riwayatPegawai.length > 2 ? ', +' + (riwayatPegawai.length - 2) + ' lainnya' : '';
          riwayatCell = '<span class="clickable-name" data-open-profile="' + esc(p.nip) + '" title="' +
            'Klik untuk lihat daftar lengkap riwayat diklat/sertifikasi (termasuk dari sertifikasi_asn)">' +
            '<span class="pill good">' + riwayatPegawai.length + ' riwayat</span><br>' +
            '<span class="muted-cell" style="font-size:12px;">' + contohNama + sisa + '</span>' +
            '</span>';
        } else {
          riwayatCell = '<span class="pill warn">Belum ada</span>';
        }
        var wajibTotal = p.pelatihan_wajib_total || 0;
        var wajibTerpenuhi = p.pelatihan_wajib_terpenuhi || 0;
        var wajibCell;
        if (wajibTotal > 0) {
          var wajibKelas = wajibTerpenuhi >= wajibTotal ? "good" : (wajibTerpenuhi > 0 ? "warn" : "crit");
          wajibCell = '<span class="clickable-name" data-open-profile="' + esc(p.nip) + '" title="Klik untuk lihat rincian Pelatihan Wajib (Dasar/Menengah/Tinggi) -- untuk MEMILIH pelatihan, pakai tombol di kolom Pelatihan">' +
            '<span class="pill ' + wajibKelas + '">' + wajibTerpenuhi + '/' + wajibTotal + ' terpenuhi</span>' +
            '</span>';
        } else {
          wajibCell = '<span class="muted-cell">-</span>';
        }
        // Kolom "Pelatihan" adalah SATU-SATUNYA tempat untuk memilih/menyimpan
        // pelatihan -- 1 tombol yang buka 1 modal berisi SEMUA yang relevan
        // buat pegawai ini (rekomendasi TIK dari okupasi_tugas, dan/atau
        // Pelatihan Wajib Dasar/Menengah/Tinggi) -- lihat openPelatihanModal.
        // Kolom "Pelatihan Wajib" di tabel cuma menampilkan ringkasan status,
        // tidak bisa dipilih dari sana.
        var dipilihTik = opsi ? dipilih.filter(function (d) { return d.nama_okupasi === opsi.nama_okupasi; }) : [];
        var dipilihWajib = dipilih.filter(function (d) { return d.nama_okupasi === WAJIB_OKUPASI_KEY; });
        var punyaOpsiTik = !!(opsi && opsi.pilihan && opsi.pilihan.length);
        var punyaOpsiWajib = wajibTotal > 0;
        if (punyaOpsiTik || punyaOpsiWajib) {
          // Tombol TETAP selalu ada & bisa diklik kapan saja -- baik untuk
          // pilih pertama kali, MAUPUN untuk nambah/ubah pilihan yang sudah
          // ada (dibuka lagi, checklist yang sudah tersimpan otomatis
          // tercentang, tinggal centang/hapus centang lalu simpan ulang).
          // Supaya jelas tombol ini juga berfungsi sebagai EDIT, labelnya
          // berubah jadi "Edit Pilihan" begitu sudah ada yang tersimpan.
          var totalDipilih = dipilihTik.length + dipilihWajib.length;
          var sudahPilih = totalDipilih > 0;
          var labelTombol = sudahPilih ? 'Edit Pilihan' : 'Pilih Pelatihan';
          var keterangan = sudahPilih
            ? '<span class="pill terpilih-info">' + totalDipilih + ' dipilih</span>'
            : '';
          pelatihanCell = '<div class="pelatihan-cell">' +
            '<button class="btn small primary" data-pilih-pelatihan="' + esc(p.nip) + '" title="' + (sudahPilih ? 'Tambah, kurangi, atau ubah pelatihan yang sudah dipilih' : 'Pilih pelatihan') + '">' + esc(labelTombol) + '</button>' +
            keterangan +
            '</div>';
        } else {
          pelatihanCell = '-';
        }
        return '<tr>' +
          '<td>' + (i + 1) + '</td>' +
          '<td>' + esc(p.nip) + '</td>' +
          '<td class="strong">' + nameLink(p.nip, p.nama) + '</td>' +
          '<td>' + esc(p.jabatan || "-") + '</td>' +
          '<td>' + esc(p.satuan_kerja || "-") + '</td>' +
          '<td>' + kategoriCell + '</td>' +
          '<td>' + areaCell + '</td>' +
          '<td>' + kodeCell + '</td>' +
          '<td>' + namaOkupasiCell + '</td>' +
          '<td>' + riwayatCell + '</td>' +
          '<td>' + wajibCell + '</td>' +
          '<td>' + pelatihanCell + '</td>' +
          '</tr>';
      }).join("");
      tbody.querySelectorAll("[data-pilih-pelatihan]").forEach(function (btn) {
        btn.addEventListener("click", function () { openPelatihanModal(btn.getAttribute("data-pilih-pelatihan")); });
      });
    }
    document.getElementById("rekomendasi-count").textContent = rows.length + " dari " + PEGAWAI.length + " pegawai ditampilkan.";
  }

  // =====================================================================
  // Modal "Pilih Pelatihan" — checklist beberapa pilihan pelatihan per
  // okupasi (dari Peta Okupasi TIK), disimpan ke database lewat
  // POST /pelatihan-pilihan.
  // =====================================================================
  function csrfToken() {
    var m = document.querySelector('meta[name="csrf-token"]');
    return m ? m.getAttribute("content") : "";
  }
  // Simpan pilihan pelatihan (TIK maupun Pelatihan Wajib, dibedakan lewat
  // namaOkupasi) ke POST /pelatihan-pilihan -- dipakai bersama supaya tidak
  // dobel-tulis logika fetch-nya.
  function simpanPelatihanDipilih(nip, namaOkupasi, pelatihan, onSuccess) {
    fetch("/pelatihan-pilihan", {
      method: "POST",
      headers: { "Content-Type": "application/json", "X-CSRF-TOKEN": csrfToken(), "Accept": "application/json" },
      body: JSON.stringify({ nip: nip, nama_okupasi: namaOkupasi, pelatihan: pelatihan })
    }).then(function (r) {
      if (!r.ok) throw new Error("gagal simpan");
      return r.json();
    }).then(onSuccess).catch(function () {
      toast("Gagal menyimpan pilihan pelatihan. Coba lagi.");
    });
  }
  // Satu modal untuk SEMUA pilihan pelatihan pegawai: seksi "Rekomendasi
  // TIK" (dari Peta Okupasi, kalau ada) dan seksi "Pelatihan Wajib"
  // (Dasar/Menengah/Tinggi, kalau ada) ditampilkan sekaligus -- isinya beda
  // sumber (okupasi_tugas vs pelatihan_wajib), tapi disimpan lewat 1 tombol
  // "Simpan Pilihan" yang sama (dikirim sebagai 2 request terpisah ke
  // POST /pelatihan-pilihan, dibedakan lewat nama_okupasi).
  function openPelatihanModal(nip) {
    var p = PEGAWAI_BY_NIP[nip];
    if (!p) return;
    var opsi = p.rekomendasi_pelatihan_opsi;
    var punyaTik = !!(opsi && opsi.pilihan && opsi.pilihan.length);
    var urutLevel = { "Dasar": 1, "Menengah": 2, "Tinggi": 3 };
    var wajibList = (p.pelatihan_wajib || []).slice().sort(function (a, b) {
      return (a.urutan_level || urutLevel[a.level] || 99) - (b.urutan_level || urutLevel[b.level] || 99);
    });
    var punyaWajib = wajibList.length > 0;
    if (!punyaTik && !punyaWajib) return;

    var dipilihTikNama = (p.pelatihan_dipilih || [])
      .filter(function (d) { return opsi && d.nama_okupasi === opsi.nama_okupasi; })
      .map(function (d) { return d.nama_pelatihan; });
    var dipilihWajibNama = (p.pelatihan_dipilih || [])
      .filter(function (d) { return d.nama_okupasi === WAJIB_OKUPASI_KEY; })
      .map(function (d) { return d.nama_pelatihan; });
    var sudahAdaPilihan = dipilihTikNama.length > 0 || dipilihWajibNama.length > 0;
    var body = document.getElementById("pelatihan-modal-body");
    var judulModal = sudahAdaPilihan ? 'Edit Pilihan Pelatihan' : 'Pilih Pelatihan';

    var tikHtml = '';
    if (punyaTik) {
      tikHtml = '<div class="profile-section-title">Rekomendasi TIK: <b>' + esc(opsi.nama_okupasi) + '</b>' + (opsi.kode ? ' (' + esc(opsi.kode) + ')' : '') +
        '</div>' +
        '<div class="pelatihan-checklist" id="pelatihan-checklist-tik">' +
        opsi.pilihan.map(function (t, i) {
          var checked = dipilihTikNama.indexOf(t.nama) >= 0 ? "checked" : "";
          return '<label class="pelatihan-check-row">' +
            '<input type="checkbox" value="' + i + '" ' + checked + '>' +
            '<span><span class="pelatihan-nama">' + esc(t.nama) + '</span>' +
            (t.kode_standar ? '<span class="pelatihan-kode">' + esc(t.kode_standar) + '</span>' : '') +
            (t.kemungkinan_sudah_diikuti ? '<span class="pelatihan-riwayat-flag" title="Kemungkinan sudah pernah diikuti (dibandingkan dari riwayat diklat), silakan cek lagi manual">⚠ Mirip riwayat: ' + esc(t.kemungkinan_sudah_diikuti) + '</span>' : '') +
            '</span></label>';
        }).join("") +
        '</div>';
    }

    var wajibHtml = '';
    if (punyaWajib) {
      wajibHtml = '<div class="profile-section-title">Pelatihan Wajib (Dasar/Menengah/Tinggi) — kategori: ' + esc(p.jabatan || "-") + '</div>' +
        '<div class="pelatihan-checklist" id="pelatihan-checklist-wajib">' +
        wajibList.map(function (w, i) {
          var checked = dipilihWajibNama.indexOf(w.nama_pelatihan) >= 0 ? "checked" : "";
          var status = w.sudah_diikuti ? '<span class="pill good">Sudah</span>' : '<span class="pill warn">Belum</span>';
          return '<label class="pelatihan-check-row">' +
            '<input type="checkbox" value="' + i + '" ' + checked + '>' +
            '<span><span class="pill" style="margin-right:6px;">' + esc(w.level) + '</span>' +
            '<span class="pelatihan-nama">' + esc(w.nama_pelatihan) + '</span> ' + status + '</span>' +
            '</label>';
        }).join("") +
        '</div>';
    }

    body.innerHTML =
      '<h3>' + judulModal + ' — ' + esc(p.nama) + '</h3>' +
      (sudahAdaPilihan ? '<div class="sub">Centang/hapus centang sesuai kebutuhan, lalu klik "Simpan Pilihan".</div>' : '') +
      tikHtml + wajibHtml +
      '<div class="modal-actions">' +
      '<button class="btn" id="pelatihan-cancel">Batal</button>' +
      '<button class="btn primary" id="pelatihan-submit">Simpan Pilihan</button>' +
      '</div>';

    document.getElementById("pelatihan-cancel").addEventListener("click", closePelatihanModal);
    document.getElementById("pelatihan-submit").addEventListener("click", function () {
      var groups = [];
      if (punyaTik) {
        var checkedTik = Array.prototype.slice.call(body.querySelectorAll('#pelatihan-checklist-tik input[type="checkbox"]:checked'));
        groups.push({
          namaOkupasi: opsi.nama_okupasi,
          pelatihan: checkedTik.map(function (c) { return opsi.pilihan[Number(c.value)]; })
        });
      }
      if (punyaWajib) {
        var checkedWajib = Array.prototype.slice.call(body.querySelectorAll('#pelatihan-checklist-wajib input[type="checkbox"]:checked'));
        groups.push({
          namaOkupasi: WAJIB_OKUPASI_KEY,
          pelatihan: checkedWajib.map(function (c) { return wajibList[Number(c.value)]; }).map(function (w) {
            return { nama: w.nama_pelatihan, kode_standar: null };
          })
        });
      }
      var totalTersimpan = groups.reduce(function (sum, g) { return sum + g.pelatihan.length; }, 0);
      // Kirim tiap grup sebagai request terpisah ke endpoint yang sama
      // (endpoint-nya menyimpan per nama_okupasi), lalu baru gabungkan cache
      // lokal & tutup modal setelah SEMUA grup selesai tersimpan.
      var i = 0;
      function simpanBerikutnya() {
        if (i >= groups.length) {
          p.pelatihan_dipilih = (p.pelatihan_dipilih || []).filter(function (d) {
            return groups.every(function (g) { return d.nama_okupasi !== g.namaOkupasi; });
          }).concat(groups.reduce(function (acc, g) {
            return acc.concat(g.pelatihan.map(function (t) {
              return { nama_okupasi: g.namaOkupasi, nama_pelatihan: t.nama, kode_standar: t.kode_standar };
            }));
          }, []));
          toast(totalTersimpan + " pilihan pelatihan tersimpan untuk " + p.nama + ".");
          closePelatihanModal();
          renderRekomendasiTable();
          return;
        }
        var g = groups[i++];
        simpanPelatihanDipilih(nip, g.namaOkupasi, g.pelatihan, simpanBerikutnya);
      }
      simpanBerikutnya();
    });
    document.getElementById("pelatihan-modal").classList.add("open");
  }
  function closePelatihanModal() { document.getElementById("pelatihan-modal").classList.remove("open"); }
  document.getElementById("pelatihan-modal").addEventListener("click", function (e) {
    if (e.target === this) closePelatihanModal();
  });
  function renderRekomendasiChart() {
    var jumlahTik = 0, jumlahNonTik = 0;
    PEGAWAI.forEach(function (p) {
      if (p.jabatan_it) jumlahTik++; else jumlahNonTik++;
    });
    var rows = [
      { label: "Sudah TIK", value: jumlahTik, color: "var(--status-good, #16a34a)" },
      { label: "Rekomendasi ke TIK", value: jumlahNonTik, color: "var(--status-critical)" }
    ];
    renderHBars(document.getElementById("chart-rekomendasi-bidang"), rows);
  }
  function initRekomendasiPage() {
    document.getElementById("rekomendasi-search").addEventListener("input", renderRekomendasiTable);
    document.getElementById("rekomendasi-filter-bidang").addEventListener("change", renderRekomendasiTable);
    renderRekomendasiChart();
    renderRekomendasiTable();
  }

  // =====================================================================
  // PER OPD
  // =====================================================================
  function opdSummary() {
    var byOpd = {};
    PEGAWAI.forEach(function (p) {
      var k = p.satuan_kerja || "Tidak Diketahui";
      if (!byOpd[k]) byOpd[k] = { opd: k, total: 0, TIK: 0, "Non TIK": 0, Manajerial: 0, belum: 0, sudah: 0, members: [] };
      byOpd[k].total++;
      byOpd[k][p.kelompok]++;
      if (p.sudah_diklat) byOpd[k].sudah++; else byOpd[k].belum++;
      byOpd[k].members.push(p);
    });
    return Object.values(byOpd).sort(function (a, b) { return b.total - a.total; });
  }

  // Daftar filter cepat yang tersedia di dalam tiap grup (OPD atau Golongan) yang
  // dibuka -- "Semua", per kelompok (TIK/Non TIK/Manajerial), dan status diklat.
  var QUICK_FILTERS = [
    { key: "semua", label: "Semua", test: function () { return true; } },
    { key: "TIK", label: "TIK", test: function (p) { return p.kelompok === "TIK"; } },
    { key: "Non TIK", label: "Non TIK", test: function (p) { return p.kelompok === "Non TIK"; } },
    { key: "Manajerial", label: "Manajerial", test: function (p) { return p.kelompok === "Manajerial"; } },
    { key: "sudah", label: "Sudah Diklat", test: function (p) { return p.sudah_diklat; } },
    { key: "belum", label: "Belum Diklat", test: function (p) { return !p.sudah_diklat; } }
  ];

  // Baris grup yang bisa dipencet untuk membuka/menutup daftar nama anggotanya.
  // Dipakai bersama oleh tabel "ASN per OPD" dan "ASN per Golongan". Setiap grup
  // yang dibuka punya toggle Semua/TIK/Non TIK/Manajerial/Sudah/Belum Diklat +
  // kotak pencarian, dan angka pill di baris utama juga bisa dipencet langsung
  // untuk membuka grup itu dengan filter yang sudah sesuai.
  function renderMiniNameList(container, members, query) {
    var q = (query || "").toLowerCase().trim();
    var filtered = members.filter(function (p) {
      if (!q) return true;
      return (p.nama || "").toLowerCase().indexOf(q) >= 0 ||
        (p.nip || "").toLowerCase().indexOf(q) >= 0 ||
        (p.jabatan || "").toLowerCase().indexOf(q) >= 0;
    });
    if (!filtered.length) {
      container.innerHTML = '<div class="mini-empty">Tidak ada nama yang cocok.</div>';
      return;
    }
    container.innerHTML = filtered.map(function (p, i) {
      return '<div class="mini-name-row" data-open-profile="' + esc(p.nip) + '">' +
        '<span class="num">' + (i + 1) + '</span>' +
        '<span class="n">' + esc(p.nama) + '</span>' +
        '<span class="j">' + esc(p.jabatan || "-") + '</span>' +
        '<span class="g">' + esc(p.golongan_ruang || "-") + ' &middot; ' + (p.sudah_diklat ? "Sudah Diklat" : "Belum Diklat") + '</span>' +
        '</div>';
    }).join("");
  }

  function attachGroupExpand(tbody, groupsByKey, colspan) {
    function closeRow(row) {
      row.classList.remove("expanded");
      var next = row.nextElementSibling;
      if (next && next.classList.contains("group-detail-row")) next.remove();
    }
    function openRow(row, key, initialFilter) {
      tbody.querySelectorAll("tr.group-row.expanded").forEach(closeRow);
      row.classList.add("expanded");
      var allMembers = ((groupsByKey[key] && groupsByKey[key].members) || [])
        .slice().sort(function (a, b) { return String(a.nama).localeCompare(String(b.nama)); });

      var filterBtns = QUICK_FILTERS.map(function (f) {
        var n = allMembers.filter(f.test).length;
        return '<button class="tab-btn filter-chip-btn' + (f.key === initialFilter ? " active" : "") + '" data-filter-key="' + f.key + '">' +
          esc(f.label) + ' <span class="cnt">(' + n + ')</span></button>';
      }).join("");

      var tr = document.createElement("tr");
      tr.className = "group-detail-row";
      tr.innerHTML = '<td colspan="' + colspan + '"><div class="detail-wrap">' +
        '<div class="filter-chip-row">' + filterBtns + '</div>' +
        '<input type="text" class="detail-search" placeholder="Cari nama pegawai di ' + esc(key) + '...">' +
        '<div class="mini-name-list"></div>' +
        '<div class="detail-count"></div>' +
        '</div></td>';
      row.parentNode.insertBefore(tr, row.nextSibling);

      var listEl = tr.querySelector(".mini-name-list");
      var countEl = tr.querySelector(".detail-count");
      var searchEl = tr.querySelector(".detail-search");
      var activeFilter = initialFilter;

      function refresh() {
        var filterDef = QUICK_FILTERS.filter(function (f) { return f.key === activeFilter; })[0] || QUICK_FILTERS[0];
        var members = allMembers.filter(filterDef.test);
        renderMiniNameList(listEl, members, searchEl.value);
        var shown = listEl.querySelectorAll(".mini-name-row").length;
        countEl.textContent = "Menampilkan " + shown + " dari " + members.length + " pegawai (" + filterDef.label + ") di " + key + ".";
      }
      tr.querySelectorAll(".filter-chip-btn").forEach(function (btn) {
        btn.addEventListener("click", function (e) {
          e.stopPropagation();
          activeFilter = btn.getAttribute("data-filter-key");
          tr.querySelectorAll(".filter-chip-btn").forEach(function (b) { b.classList.toggle("active", b === btn); });
          refresh();
        });
      });
      refresh();
      searchEl.addEventListener("input", refresh);
      searchEl.addEventListener("click", function (e) { e.stopPropagation(); });
    }

    tbody.querySelectorAll("tr.group-row").forEach(function (row) {
      row.addEventListener("click", function (e) {
        if (e.target.closest("[data-open-profile], .detail-search, .filter-chip-btn")) return;
        var key = row.getAttribute("data-group-key");
        var quick = e.target.closest("[data-quick-filter]");
        if (quick) {
          openRow(row, key, quick.getAttribute("data-quick-filter"));
          return;
        }
        if (row.classList.contains("expanded")) { closeRow(row); return; }
        openRow(row, key, "semua");
      });
    });
  }

  function renderOpdTable() {
    var q = (document.getElementById("opd-search").value || "").toLowerCase().trim();
    var rows = opdSummary();
    if (q) rows = rows.filter(function (r) { return r.opd.toLowerCase().indexOf(q) >= 0; });
    var byKey = {};
    rows.forEach(function (r) { byKey[r.opd] = r; });
    var tbody = document.querySelector("#table-opd tbody");
    tbody.innerHTML = rows.map(function (r, i) {
      return '<tr class="group-row" data-group-key="' + esc(r.opd) + '">' +
        '<td>' + (i + 1) + '</td>' +
        '<td class="strong"><span class="chev">&#9656;</span>' + esc(r.opd) + '</td>' +
        '<td>' + fmtInt(r.total) + '</td>' +
        '<td><span class="pill tik" data-quick-filter="TIK">' + r.TIK + '</span></td>' +
        '<td><span class="pill nontik" data-quick-filter="Non TIK">' + r["Non TIK"] + '</span></td>' +
        '<td><span class="pill manajerial" data-quick-filter="Manajerial">' + r.Manajerial + '</span></td>' +
        '<td>' + (r.belum > 0 ? '<span class="pill warn" data-quick-filter="belum">' + r.belum + '</span>' : '<span class="pill good" data-quick-filter="belum">0</span>') + '</td>' +
        '</tr>';
    }).join("");
    attachGroupExpand(tbody, byKey, 7);
    document.getElementById("opd-count").textContent = rows.length + " OPD/satuan kerja terdata. Klik baris atau salah satu angka (TIK/Non TIK/Manajerial/Belum Diklat) untuk melihat daftar namanya.";
  }
  function renderOpdChart() {
    var top = opdSummary().slice().sort(function (a, b) { return b.total - a.total; }).slice(0, 10);
    renderHBars(document.getElementById("chart-opd-top-page"), top.map(function (r, i) {
      return { label: r.opd, value: r.total, color: seriesColor(i) };
    }));
  }
  function initOpdPage() {
    document.getElementById("opd-search").addEventListener("input", renderOpdTable);
    renderOpdChart();
    renderOpdTable();
  }

  // =====================================================================
  // PER GOLONGAN
  // =====================================================================
  function golonganSummary() {
    var byGol = {};
    PEGAWAI.forEach(function (p) {
      var k = p.golongan_ruang || "Tidak Diketahui";
      if (!byGol[k]) byGol[k] = { gol: k, total: 0, TIK: 0, "Non TIK": 0, Manajerial: 0, jpSum: 0, belum: 0, sudah: 0, members: [] };
      byGol[k].total++;
      byGol[k][p.kelompok]++;
      byGol[k].jpSum += (p.total_jp || 0);
      if (p.sudah_diklat) byGol[k].sudah++; else byGol[k].belum++;
      byGol[k].members.push(p);
    });
    return Object.values(byGol).sort(function (a, b) { return a.gol.localeCompare(b.gol); });
  }
  function renderGolonganPage() {
    var rows = golonganSummary();
    var byKey = {};
    rows.forEach(function (r) { byKey[r.gol] = r; });
    renderHBars(document.getElementById("chart-golongan-full"), rows.map(function (r, i) {
      return { label: r.gol, value: r.total, color: seriesColor(i) };
    }));
    var tbody = document.querySelector("#table-golongan tbody");
    tbody.innerHTML = rows.map(function (r, i) {
      var avgJp = r.total ? Math.round(r.jpSum / r.total) : 0;
      return '<tr class="group-row" data-group-key="' + esc(r.gol) + '">' +
        '<td>' + (i + 1) + '</td>' +
        '<td class="strong"><span class="chev">&#9656;</span>' + esc(r.gol) + '</td>' +
        '<td>' + fmtInt(r.total) + '</td>' +
        '<td><span class="pill tik" data-quick-filter="TIK">' + r.TIK + '</span></td>' +
        '<td><span class="pill nontik" data-quick-filter="Non TIK">' + r["Non TIK"] + '</span></td>' +
        '<td><span class="pill manajerial" data-quick-filter="Manajerial">' + r.Manajerial + '</span></td>' +
        '<td>' + avgJp + ' JP</td>' +
        '</tr>';
    }).join("");
    attachGroupExpand(tbody, byKey, 7);
  }

  // =====================================================================
  // Init all
  // =====================================================================
  renderRingkasan();
  initProfilPage();
  initSertifikatPage();
  initBersertifikatPage();
  initRiwayatPage();
  initCariDiklatPage();
  initSudahPage();
  initBelumPage();
  initRekomendasiPage();
  initOpdPage();
  renderGolonganPage();

})();
