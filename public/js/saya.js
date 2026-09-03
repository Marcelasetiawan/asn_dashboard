(function () {
  "use strict";

  var WAJIB_OKUPASI_KEY = "Pelatihan Wajib";
  var nip = window.SAYA_NIP;
  var csrfToken = window.sayaNav.csrfToken;
  var toast = window.sayaNav.toast;

  function simpanPelatihanDipilih(namaOkupasi, pelatihan, onSuccess) {
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

  var form = document.getElementById("pelatihan-form");

  // Kirim SEMUA checkbox yang lagi tercentang di form (baik grup TIK
  // maupun Wajib) ke server, lalu reload halaman supaya tampilan (badge
  // jumlah dipilih, daftar "Sudah Dipilih", status checkbox) sinkron
  // dengan data yang BENERAN tersimpan di database -- bukan cuma asumsi
  // di sisi client.
  function simpanSemuaPilihan() {
    if (!form) return;
    var groups = [];

    var tikChecked = Array.prototype.slice.call(form.querySelectorAll('input[data-group="tik"]:checked'));
    if (form.querySelector('input[data-group="tik"]')) {
      groups.push({
        namaOkupasi: form.getAttribute("data-okupasi-tik"),
        pelatihan: tikChecked.map(function (c) {
          return { nama: c.getAttribute("data-nama"), kode_standar: c.getAttribute("data-kode") || null };
        })
      });
    }

    var wajibChecked = Array.prototype.slice.call(form.querySelectorAll('input[data-group="wajib"]:checked'));
    if (form.querySelector('input[data-group="wajib"]')) {
      groups.push({
        namaOkupasi: WAJIB_OKUPASI_KEY,
        pelatihan: wajibChecked.map(function (c) {
          return { nama: c.getAttribute("data-nama"), kode_standar: null };
        })
      });
    }

    var total = groups.reduce(function (sum, g) { return sum + g.pelatihan.length; }, 0);
    var i = 0;
    function next() {
      if (i >= groups.length) {
        toast(total + " pelatihan tersimpan sebagai pilihan Anda.");
        setTimeout(function () { window.location.reload(); }, 600);
        return;
      }
      var g = groups[i++];
      simpanPelatihanDipilih(g.namaOkupasi, g.pelatihan, next);
    }
    next();
  }

  var submitBtn = document.getElementById("pelatihan-submit");
  if (submitBtn) {
    submitBtn.addEventListener("click", simpanSemuaPilihan);
  }

  // Tombol "Hapus" di kartu "Pelatihan yang Sudah Dipilih" -- hapus
  // centang checkbox yang bersangkutan di form checklist di bawah, lalu
  // langsung simpan ulang (supaya tidak perlu scroll manual & centang
  // sendiri).
  document.querySelectorAll("[data-hapus-pilihan]").forEach(function (btn) {
    btn.addEventListener("click", function () {
      if (!form) return;
      var group = btn.getAttribute("data-group");
      var nama = btn.getAttribute("data-nama");
      // Dicari manual (bukan lewat selector string) supaya nama pelatihan
      // yang mengandung spasi/tanda kurung/dsb tidak perlu di-escape ke
      // sintaks CSS selector.
      var checkboxes = Array.prototype.slice.call(form.querySelectorAll('input[data-group="' + group + '"]'));
      var checkbox = checkboxes.filter(function (c) { return c.getAttribute("data-nama") === nama; })[0];
      if (checkbox) checkbox.checked = false;
      simpanSemuaPilihan();
    });
  });
})();
