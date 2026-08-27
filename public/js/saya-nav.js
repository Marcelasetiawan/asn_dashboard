(function () {
  "use strict";

  // Toggle sidebar di layar sempit/HP -- salinan ringan dari logika yang
  // sama di public/js/dashboard.js (punya dashboard admin), dipisah jadi
  // file sendiri karena halaman "Profil Saya" tidak memuat dashboard.js
  // (yang isinya berat & khusus data seluruh pegawai).
  var hamburgerBtn = document.getElementById("hamburgerBtn");
  var sidebarEl = document.getElementById("sidebar");
  var sidebarBackdrop = document.getElementById("sidebarBackdrop");

  function openSidebar() {
    sidebarEl.classList.add("open");
    sidebarBackdrop.classList.add("show");
    hamburgerBtn.classList.add("open");
    hamburgerBtn.setAttribute("aria-label", "Tutup menu");
  }
  function closeSidebar() {
    sidebarEl.classList.remove("open");
    sidebarBackdrop.classList.remove("show");
    hamburgerBtn.classList.remove("open");
    hamburgerBtn.setAttribute("aria-label", "Buka menu");
  }
  if (hamburgerBtn && sidebarEl && sidebarBackdrop) {
    hamburgerBtn.addEventListener("click", function () {
      if (sidebarEl.classList.contains("open")) closeSidebar();
      else openSidebar();
    });
    sidebarBackdrop.addEventListener("click", closeSidebar);
  }

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
