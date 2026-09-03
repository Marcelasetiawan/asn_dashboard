(function () {
  "use strict";

  // Tombol lipat/buka sidebar -- salinan ringan dari logika yang sama di
  // public/js/dashboard.js (punya dashboard admin), dipisah jadi file
  // sendiri karena halaman "Profil Saya" tidak memuat dashboard.js (yang
  // isinya berat & khusus data seluruh pegawai).
  var sidebarEl = document.getElementById("sidebar");
  if (sidebarEl) {
    document.querySelectorAll(".sidebar-toggle").forEach(function (btn) {
      btn.addEventListener("click", function () { sidebarEl.classList.toggle("collapsed"); });
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
