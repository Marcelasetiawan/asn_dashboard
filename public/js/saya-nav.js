(function () {
  "use strict";

  // Tombol lipat/buka sidebar -- salinan ringan dari logika yang sama di
  // public/js/dashboard.js (punya dashboard admin), dipisah jadi file
  // sendiri karena halaman "Profil Saya" tidak memuat dashboard.js (yang
  // isinya berat & khusus data seluruh pegawai).
  var sidebarEl = document.getElementById("sidebar");
  var sidebarBackdropEl = document.getElementById("sidebar-backdrop");
  var MOBILE_BREAKPOINT = 640;
  function isMobileWidth() { return window.innerWidth <= MOBILE_BREAKPOINT; }
  function syncSidebarBackdrop() {
    if (!sidebarBackdropEl || !sidebarEl) return;
    sidebarBackdropEl.classList.toggle("show", isMobileWidth() && !sidebarEl.classList.contains("collapsed"));
  }
  if (sidebarEl) {
    if (isMobileWidth()) sidebarEl.classList.add("collapsed");
    document.querySelectorAll(".sidebar-toggle").forEach(function (btn) {
      btn.addEventListener("click", function () {
        sidebarEl.classList.toggle("collapsed");
        syncSidebarBackdrop();
      });
    });
    if (sidebarBackdropEl) {
      sidebarBackdropEl.addEventListener("click", function () {
        sidebarEl.classList.add("collapsed");
        syncSidebarBackdrop();
      });
    }
    var resizeTimer;
    window.addEventListener("resize", function () {
      clearTimeout(resizeTimer);
      resizeTimer = setTimeout(syncSidebarBackdrop, 100);
    });
  }

  // Konfirmasi sebelum benar-benar logout -- klik "Keluar" tidak langsung
  // submit form, tampilkan modal konfirmasi dulu.
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

  // Tutup modal dengan Esc + pindahkan fokus ke dalamnya saat dibuka
  // (lihat penjelasan yang sama di dashboard.js).
  document.addEventListener("keydown", function (e) {
    if (e.key !== "Escape") return;
    document.querySelectorAll(".modal-backdrop.open").forEach(function (m) {
      m.classList.remove("open");
    });
  });
  document.querySelectorAll(".modal-backdrop").forEach(function (backdrop) {
    var inner = backdrop.querySelector(".modal");
    if (!inner) return;
    if (!inner.hasAttribute("tabindex")) inner.setAttribute("tabindex", "-1");
    new MutationObserver(function () {
      if (backdrop.classList.contains("open")) inner.focus({ preventScroll: true });
    }).observe(backdrop, { attributes: true, attributeFilter: ["class"] });
  });

  // Tombol tampil/sembunyikan password -- generik, dipakai di halaman
  // Pengaturan Akun (3 field: password lama/baru/ulangi). Cari input lewat
  // atribut data-toggle-password="<id-input>" supaya satu handler ini bisa
  // dipakai berapa pun banyaknya field password di halaman ini.
  document.querySelectorAll("[data-toggle-password]").forEach(function (btn) {
    var input = document.getElementById(btn.getAttribute("data-toggle-password"));
    if (!input) return;
    btn.addEventListener("click", function () {
      input.type = input.type === "password" ? "text" : "password";
    });
  });

  function csrfToken() {
    var m = document.querySelector('meta[name="csrf-token"]');
    return m ? m.getAttribute("content") : "";
  }
  var toastTimer;
  function toast(msg) {
    var el = document.getElementById("toast");
    if (!el) return;
    el.textContent = msg;
    el.classList.add("show");
    clearTimeout(toastTimer);
    toastTimer = setTimeout(function () { el.classList.remove("show"); }, 3200);
  }
  window.sayaNav = { csrfToken: csrfToken, toast: toast };
})();
