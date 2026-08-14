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
    flags += p.sudah_diklat ? '<span class="pill good">' + p.jumlah_diklat + ' Diklat Diikuti</span>' : '<span class="pill warn">Belum Pernah Diklat</span>';

    var kv = [
      ["NIP", p.nip], ["Jenis Kelamin", p.jenis_kelamin === "L" ? "Laki-laki" : (p.jenis_kelamin === "P" ? "Perempuan" : "-")],
      ["Status Kepegawaian", p.status || "-"], ["Golongan / Ruang", p.golongan_ruang || "-"],
      ["Eselon", p.eselon || "- (Non Struktural)"], ["Jabatan", p.jabatan || "-"],
      ["Satuan Kerja / OPD", p.satuan_kerja || "-"], ["Pendidikan Terakhir", (p.pendidikan || "-") + (p.tahun_lulus ? " (lulus " + p.tahun_lulus + ")" : "")],
      ["Gelar Depan", p.gelar_depan || "-"], ["Gelar Belakang", p.gelar_belakang || "-"],
      ["Total JP Diklat", fmtInt(p.total_jp) + " JP"], ["Sertifikat Belum Lengkap", p.sertifikat_kurang > 0 ? p.sertifikat_kurang + " riwayat" : "Tidak ada"]
    ];
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
  // Hamburger menu (buka/tutup sidebar di layar sempit/HP)
  // -------------------------------------------------------------------
  var hamburgerBtn = document.getElementById("hamburgerBtn");
  var sidebarEl = document.getElementById("sidebar");
  var sidebarBackdrop = document.getElementById("sidebarBackdrop");
  function openSidebar() {
    sidebarEl.classList.add("open");
    sidebarBackdrop.classList.add("show");
  }
  function closeSidebar() {
    sidebarEl.classList.remove("open");
    sidebarBackdrop.classList.remove("show");
  }
  if (hamburgerBtn && sidebarEl && sidebarBackdrop) {
    hamburgerBtn.addEventListener("click", function () {
      if (sidebarEl.classList.contains("open")) closeSidebar();
      else openSidebar();
    });
    sidebarBackdrop.addEventListener("click", closeSidebar);
  }

  // -------------------------------------------------------------------
  // Navigation
  // -------------------------------------------------------------------
  var pageTitles = {
    ringkasan: ["Ringkasan", "Gambaran umum kondisi SDM & pengembangan kompetensi"],
    profil: ["Profil Pegawai", "Data ASN per kelompok — dapat difilter & dicari"],
    sertifikat: ["Upload Sertifikat", "Riwayat diklat yang sertifikatnya belum lengkap"],
    bersertifikat: ["Sudah Bersertifikat", "ASN yang sudah ikut pelatihan dan punya sertifikat lengkap"],
    riwayat: ["Riwayat Kursus", "Seluruh riwayat pelatihan yang tercatat"],
    caridiklat: ["Cari Diklat", "Cari program diklat & lihat daftar pesertanya"],
    sudah: ["Sudah Pelatihan", "ASN yang sudah pernah mengikuti diklat"],
    belum: ["Belum Pelatihan", "ASN yang belum pernah mengikuti diklat"],
    rekomendasi: ["Rekomendasi Pelatihan", "Gelar bidang tertentu namun jabatan tidak sesuai"],
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
      { label: "Total ASN Terdata", value: total, sub: "seluruh pegawai" },
      { label: "ASN TIK", value: byKelompok["TIK"], sub: (Math.round(byKelompok["TIK"] / total * 1000) / 10) + "% dari total", color: "var(--series-1)" },
      { label: "ASN Non TIK", value: byKelompok["Non TIK"], sub: (Math.round(byKelompok["Non TIK"] / total * 1000) / 10) + "% dari total", color: "var(--series-2)" },
      { label: "ASN Manajerial", value: byKelompok["Manajerial"], sub: (Math.round(byKelompok["Manajerial"] / total * 1000) / 10) + "% dari total", color: "var(--series-3)" },
      { label: "Sudah Ikut Diklat", value: sudahDiklat, sub: (Math.round(sudahDiklat / total * 1000) / 10) + "% dari total", color: "var(--status-good)" },
      { label: "Belum Ikut Diklat", value: belumDiklat, sub: "perlu prioritas", color: "var(--status-serious)" },
      { label: "Rekomendasi Pelatihan", value: rekomendasi, sub: "gelar tak sesuai jabatan (semua bidang)", color: "var(--status-critical)" },
      { label: "Sertifikat Belum Lengkap", value: sertifikatKurang, sub: "dari " + DIKLAT.length + " riwayat diklat", color: "var(--status-warning)" }
    ];
    var html = "";
    tiles.forEach(function (t) {
      var pct = Math.min(100, Math.round((t.value / total) * 100));
      html += '<div class="tile"><div class="label">' + esc(t.label) + '</div>' +
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
    renderHBars(document.getElementById("chart-opd-top"), topOpd.map(function (r) {
      return { label: r.label, value: r.value, color: "var(--series-1)" };
    }));

    // Golongan chart
    var byGol = {};
    PEGAWAI.forEach(function (p) {
      var k = p.golongan_ruang || "Tidak Diketahui";
      byGol[k] = (byGol[k] || 0) + 1;
    });
    var golOrder = Object.keys(byGol).sort();
    renderHBars(document.getElementById("chart-golongan"), golOrder.map(function (k) {
      return { label: k, value: byGol[k], color: "var(--series-4)" };
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
    var rows = Object.keys(byGol).sort().map(function (k) {
      return { label: k, value: byGol[k], color: SERIES[currentProfilTab] || "var(--series-1)" };
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
      tbody.innerHTML = rows.slice(0, 500).map(function (p, i) {
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
    document.getElementById("profil-count").textContent =
      "Menampilkan " + Math.min(rows.length, 500) + " dari " + rows.length + " pegawai" + (rows.length > 500 ? " (dibatasi 500 baris pertama, persempit pencarian untuk melihat lebih spesifik)" : "");
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
    var badge = document.getElementById("badge-sertifikat");
    badge.textContent = kurang;
    badge.style.display = kurang ? "inline-block" : "none";
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
    var badge = document.getElementById("badge-bersertifikat");
    badge.textContent = rows.length;
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
      tbody.innerHTML = rows.slice(0, 500).map(function (r, i) {
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
      "Menampilkan " + Math.min(rows.length, 500) + " dari " + rows.length + " pegawai yang sudah bersertifikat";
  }
  function initBersertifikatPage() {
    renderBersertifikatTiles();
    renderBersertifikatChart();
    renderBersertifikatTable();
    document.getElementById("bersertifikat-search").addEventListener("input", renderBersertifikatTable);
    document.getElementById("bersertifikat-filter-kelompok").addEventListener("change", renderBersertifikatTable);
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
      tbody.innerHTML = rows.slice(0, 500).map(function (r, pos) {
        var d = r.d, idx = r.idx;
        var p = PEGAWAI_BY_NIP[d.nip] || {};
        var lengkap = d.sertifikat_lengkap || uploadedOverrides[idx];
        return '<tr>' +
          '<td>' + (pos + 1) + '</td>' +
          '<td class="strong">' + nameLink(d.nip, p.nama || d.nip) + '</td>' +
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
    document.getElementById("riwayat-count").textContent =
      "Menampilkan " + Math.min(rows.length, 500) + " dari " + rows.length + " riwayat diklat";
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
    renderHBars(document.getElementById("chart-caridiklat-top"), top.map(function (g) {
      return { label: g.key, value: g.jumlah, color: "var(--series-1)" };
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
    var badge = document.getElementById("badge-sudah");
    badge.textContent = rows.length;
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
      tbody.innerHTML = rows.slice(0, 500).map(function (p, i) {
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
      "Menampilkan " + Math.min(rows.length, 500) + " dari " + rows.length + " pegawai yang sudah pernah mengikuti pelatihan";
  }
  function initSudahPage() {
    renderSudahTiles();
    renderSudahChart();
    renderSudahTable();
    document.getElementById("sudah-search").addEventListener("input", renderSudahTable);
    document.getElementById("sudah-filter-kelompok").addEventListener("change", renderSudahTable);
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
      tbody.innerHTML = rows.slice(0, 500).map(function (p, i) {
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
      "Menampilkan " + Math.min(rows.length, 500) + " dari " + rows.length + " pegawai yang belum pernah diklat";
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
    renderBelumTiles();
    renderBelumChart();
    renderBelumTable();
    var badge = document.getElementById("badge-belum");
    var n = PEGAWAI.filter(function (p) { return !p.sudah_diklat; }).length;
    badge.textContent = n;
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
    var bidang = document.getElementById("rekomendasi-filter-bidang").value;
    var rows = PEGAWAI.filter(function (p) { return p.rekomendasi_pelatihan; });
    if (bidang) rows = rows.filter(function (p) { return p.bidang_gelar === bidang; });
    if (q) rows = rows.filter(function (p) {
      return (p.nama || "").toLowerCase().indexOf(q) >= 0 ||
        (p.nip || "").toLowerCase().indexOf(q) >= 0 ||
        (p.jabatan || "").toLowerCase().indexOf(q) >= 0;
    });
    var tbody = document.querySelector("#table-rekomendasi tbody");
    if (!rows.length) {
      tbody.innerHTML = '<tr><td colspan="8"><div class="empty-state"><div class="big">&#128269;</div>Tidak ada yang cocok.</div></td></tr>';
    } else {
      tbody.innerHTML = rows.map(function (p, i) {
        return '<tr>' +
          '<td>' + (i + 1) + '</td>' +
          '<td>' + esc(p.nip) + '</td>' +
          '<td class="strong">' + nameLink(p.nip, p.nama) + '</td>' +
          '<td>' + esc(p.gelar_belakang || "-") + '</td>' +
          '<td><span class="pill ' + bidangPillClass(p.bidang_gelar) + '">' + esc(p.bidang_gelar || "-") + '</span></td>' +
          '<td>' + esc(p.jabatan || "-") + '</td>' +
          '<td>' + esc(p.satuan_kerja || "-") + '</td>' +
          '<td><span class="pill crit" title="Penyegaran bidang ' + esc(p.bidang_gelar || "-") + '">Penyegaran Kompetensi</span></td>' +
          '</tr>';
      }).join("");
    }
    document.getElementById("rekomendasi-count").textContent = rows.length + " pegawai direkomendasikan mengikuti pelatihan penyegaran kompetensi sesuai bidang gelarnya.";
  }
  function renderRekomendasiChart() {
    var byBidang = {};
    PEGAWAI.filter(function (p) { return p.rekomendasi_pelatihan; }).forEach(function (p) {
      var k = p.bidang_gelar || "Lainnya";
      byBidang[k] = (byBidang[k] || 0) + 1;
    });
    var rows = Object.keys(byBidang).map(function (k) {
      return { label: k, value: byBidang[k], color: "var(--status-critical)" };
    }).sort(function (a, b) { return b.value - a.value; });
    renderHBars(document.getElementById("chart-rekomendasi-bidang"), rows);
  }
  function initRekomendasiPage() {
    var bidangList = Array.from(new Set(PEGAWAI.filter(function (p) { return p.rekomendasi_pelatihan; }).map(function (p) { return p.bidang_gelar; }))).sort();
    fillSelectOptions(document.getElementById("rekomendasi-filter-bidang"), bidangList, "Semua Bidang");
    document.getElementById("rekomendasi-search").addEventListener("input", renderRekomendasiTable);
    document.getElementById("rekomendasi-filter-bidang").addEventListener("change", renderRekomendasiTable);
    renderRekomendasiChart();
    renderRekomendasiTable();
    var badge = document.getElementById("badge-rekomendasi");
    badge.textContent = PEGAWAI.filter(function (p) { return p.rekomendasi_pelatihan; }).length;
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
    tbody.innerHTML = rows.map(function (r) {
      return '<tr class="group-row" data-group-key="' + esc(r.opd) + '">' +
        '<td class="strong"><span class="chev">&#9656;</span>' + esc(r.opd) + '</td>' +
        '<td>' + fmtInt(r.total) + '</td>' +
        '<td><span class="pill tik" data-quick-filter="TIK">' + r.TIK + '</span></td>' +
        '<td><span class="pill nontik" data-quick-filter="Non TIK">' + r["Non TIK"] + '</span></td>' +
        '<td><span class="pill manajerial" data-quick-filter="Manajerial">' + r.Manajerial + '</span></td>' +
        '<td>' + (r.belum > 0 ? '<span class="pill warn" data-quick-filter="belum">' + r.belum + '</span>' : '<span class="pill good" data-quick-filter="belum">0</span>') + '</td>' +
        '</tr>';
    }).join("");
    attachGroupExpand(tbody, byKey, 6);
    document.getElementById("opd-count").textContent = rows.length + " OPD/satuan kerja terdata. Klik baris atau salah satu angka (TIK/Non TIK/Manajerial/Belum Diklat) untuk melihat daftar namanya.";
  }
  function renderOpdChart() {
    var top = opdSummary().slice().sort(function (a, b) { return b.total - a.total; }).slice(0, 10);
    renderHBars(document.getElementById("chart-opd-top-page"), top.map(function (r) {
      return { label: r.opd, value: r.total, color: "var(--series-1)" };
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
    renderHBars(document.getElementById("chart-golongan-full"), rows.map(function (r) {
      return { label: r.gol, value: r.total, color: "var(--series-4)" };
    }));
    var tbody = document.querySelector("#table-golongan tbody");
    tbody.innerHTML = rows.map(function (r) {
      var avgJp = r.total ? Math.round(r.jpSum / r.total) : 0;
      return '<tr class="group-row" data-group-key="' + esc(r.gol) + '">' +
        '<td class="strong"><span class="chev">&#9656;</span>' + esc(r.gol) + '</td>' +
        '<td>' + fmtInt(r.total) + '</td>' +
        '<td><span class="pill tik" data-quick-filter="TIK">' + r.TIK + '</span></td>' +
        '<td><span class="pill nontik" data-quick-filter="Non TIK">' + r["Non TIK"] + '</span></td>' +
        '<td><span class="pill manajerial" data-quick-filter="Manajerial">' + r.Manajerial + '</span></td>' +
        '<td>' + avgJp + ' JP</td>' +
        '</tr>';
    }).join("");
    attachGroupExpand(tbody, byKey, 6);
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
