# Dashboard Bangkom ASN — Kabupaten Banyuwangi

Dashboard pengembangan kompetensi (Bangkom) ASN Kabupaten Banyuwangi. Dibangun dengan Laravel, punya 2 role:

- **Admin** — dashboard lengkap: rekapitulasi seluruh ASN, riwayat diklat, sertifikat, rekomendasi pelatihan TIK, per OPD, per golongan, dsb.
- **ASN** (pengguna biasa) — portal pribadi untuk melihat data & riwayat diklat sendiri, memilih pelatihan wajib/rekomendasi, mengunggah sertifikat, dan mengubah data kontak/password sendiri.

## Daftar Isi

1. [Kebutuhan](#kebutuhan)
2. [Cara Menjalankan (Setup)](#cara-menjalankan-setup)
3. [Login](#login)
4. [Troubleshooting](#troubleshooting)
5. [Struktur Singkat](#struktur-singkat)
6. [Tim & Alur Kerja](#tim--alur-kerja)

## Kebutuhan

- PHP **^8.3**
- [Composer](https://getcomposer.org/)
- MySQL (atau database lain yang didukung Laravel — sesuaikan `.env`)
- Laravel **^13.8** (terpasang otomatis lewat Composer, tidak perlu instal manual)

Node/npm **tidak wajib** untuk menjalankan aplikasinya — CSS & JS dashboard dipakai langsung sebagai file statis di `public/css` dan `public/js`, tidak lewat build Vite. Node/npm cuma dipakai kalau mau utak-atik build asset dengan Tailwind/Vite (lihat `package.json`).

## Cara Menjalankan (Setup)

### 1. Clone & install dependency

```bash
git clone https://github.com/Marcelasetiawan/Dashboard_Pelatihan.git
cd Dashboard_Pelatihan
composer install
```

### 2. Siapkan file environment

```bash
cp .env.example .env
php artisan key:generate
```

Buka `.env`, lalu sesuaikan koneksi database (default project ini pakai MySQL, bukan sqlite):

```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=nama_database_kamu
DB_USERNAME=root
DB_PASSWORD=
```

Buat database kosong dengan nama yang sama di MySQL (lewat phpMyAdmin/HeidiSQL/Laragon, dsb).

### 3. Siapkan file data sumber (WAJIB sebelum seeding)

Folder `database/csv/` **sengaja tidak ikut di-commit ke Git** — isinya data pribadi ASN asli (NIP, nama, riwayat diklat, dst.) sehingga tidak boleh diunggah ke repository publik.

Sebelum menjalankan seeder, minta file-file berikut ke pemilik proyek dan taruh di folder `database/csv/`:

```
database/csv/
├── hasil akhir.xlsx
├── diklat_siasn.xlsx
├── sertifikasi asn.xlsx
├── pegawai_json_pns_pppk.xlsx
├── pegawais_2026.xlsx
├── master_jabfung.csv
├── master_jabpel.csv
├── master_genpos.csv
├── master_unor.csv
├── jabatan_okupasi_mapping.csv
├── okupasi.csv
└── okupasi_tugas.csv
```

Tanpa file-file ini, proses seeding di langkah berikutnya akan gagal.

### 4. Migrasi & isi data

```bash
php artisan migrate:fresh --seed
```

Proses ini membaca seluruh file di `database/csv/`, lalu mengisi ±14.800 data pegawai dan ±91.000 riwayat diklat — **butuh waktu beberapa menit**, tunggu sampai selesai (jangan dihentikan di tengah jalan).

### 5. Aktifkan storage untuk upload sertifikat

```bash
php artisan storage:link
```

Wajib dijalankan supaya fitur "Unggah Sertifikat" di portal ASN bisa menyimpan & menampilkan berkasnya.

### 6. Jalankan server

```bash
php artisan serve
```

Buka [http://127.0.0.1:8000](http://127.0.0.1:8000) di browser.

> Kalau pakai Laragon, cukup arahkan virtual host ke folder project ini seperti biasa (root project, bukan folder `public/` — Laravel di Laragon otomatis diarahkan ke `public/` lewat konfigurasi bawaannya) — tidak perlu `php artisan serve`.

## Login

| Role | Username | Password awal |
|---|---|---|
| Admin | `admin` | `admin123` |
| ASN | NIP masing-masing pegawai | sama dengan NIP-nya |

Password ASN bisa diganti sendiri lewat menu "Pengaturan Akun" setelah login.

> Akun di atas untuk development lokal. Ganti password default sebelum aplikasi ini dipakai dengan data sungguhan (production).

## Troubleshooting

**Halaman login bisa dibuka, tapi login selalu gagal padahal username/password sudah benar.**

Artinya tabel `users` di database masih kosong — biasanya karena proses `php artisan migrate:fresh --seed` di langkah 4 sempat gagal di tengah jalan (paling sering gara-gara file di `database/csv/` belum lengkap) sehingga seeder yang membuat akun login (`UserSeeder`) tidak sempat jalan. Halaman login sendiri tetap bisa dibuka karena tidak butuh data apapun dari database, jadi ini gampang bikin bingung.

Cara cek:

```bash
php artisan tinker --execute="echo App\Models\User::count();"
```

- Hasilnya **0** → database belum ke-seed. Lengkapi dulu semua file di `database/csv/` (lihat langkah 3), lalu ulangi `php artisan migrate:fresh --seed` sampai benar-benar selesai tanpa error di tengah jalan.
- Hasilnya **14878** → database sudah ke-seed dengan benar. Kalau masih gagal login, cek lagi NIP yang dipakai (harus persis sama dengan yang ada di data, termasuk tanpa spasi).

## Struktur Singkat

- `app/Services/BangkomDashboardData.php` — logika utama penyusunan data dashboard (dipakai admin & portal ASN).
- `app/Services/DashboardImportNormalizer.php` — pembaca & pembersih data mentah dari `database/csv/`.
- `resources/views/dashboard.blade.php` + `public/js/dashboard.js` — dashboard admin (satu halaman, navigasi lewat JS).
- `resources/views/saya/*` + `public/js/saya.js` — portal ASN (multi-halaman, per menu).
- `resources/views/auth/login.blade.php` — halaman login.

## Tim & Alur Kerja

Repo ini dikerjakan bareng oleh 4 orang, masing-masing di branch sendiri supaya tidak saling tabrakan:

| Nama | Username GitHub | Branch |
|---|---|---|
| Marcela (pemilik repo) | `Marcelasetiawan` | `marcela-fondasi-backend` |
| Bilbi | `FaraditaBilbianaEkaSaputeri` | `bilbi` |
| Amal | `aamaliadiin` | `amal` |
| Ika | `Ika-byte` | `ika` |

**Aturan main:**

1. **Jangan commit langsung ke `main`.** Branch `main` adalah versi stabil/gabungan — semua orang kerja di branch sendiri dulu.
2. Sebelum mulai kerja, pastikan branch kamu update dengan `main` terbaru:
   ```bash
   git checkout <branch-kamu>
   git pull origin main
   ```
3. Commit & push seperti biasa di branch masing-masing:
   ```bash
   git add .
   git commit -m "Deskripsi singkat perubahan"
   git push origin <branch-kamu>
   ```
4. Kalau pekerjaan sudah siap digabung, buka **Pull Request** dari branch kamu ke `main` di GitHub (jangan langsung merge sendiri kalau perubahannya besar — minta salah satu teman review dulu).
5. Semua yang pernah commit (lewat branch atau PR yang di-merge) otomatis muncul di halaman **Contributors** repo ini di GitHub.

> Supaya bisa push langsung ke repo ini (bukan lewat fork), pemilik repo perlu menambahkan tiap orang sebagai **Collaborator** dulu: GitHub → repo ini → *Settings* → *Collaborators* → *Add people* → masukkan username GitHub masing-masing.
