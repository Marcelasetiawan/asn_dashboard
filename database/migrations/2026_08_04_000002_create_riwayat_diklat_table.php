<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabel `riwayat_diklat` — kolomnya mengikuti persis 6 kolom tambahan
     * di Sheet "Riwayat Bangkom 2 Th. Terakhir":
     * Jenis Sertifikasi | Nama (diklat) | No. Sertifikat | Penyelenggara | Pelaksanaan | JP
     * + kolom nip untuk menghubungkan ke tabel pegawai (foreign key).
     */
    public function up(): void
    {
        Schema::create('riwayat_diklat', function (Blueprint $table) {
            $table->id();
            $table->string('nip', 30);                          // relasi ke pegawai.nip
            $table->string('jenis_sertifikasi', 100)->nullable(); // "Jenis Sertifikasi"
            $table->text('nama_diklat');                         // "Nama" (nama diklat)
            $table->text('no_sertifikat')->nullable();           // "No. Sertifikat"
            $table->text('penyelenggara')->nullable();           // "Penyelenggara"
            $table->string('pelaksanaan', 50)->nullable();       // "Pelaksanaan"
            $table->integer('jp')->nullable();                   // "JP"
            $table->timestamps();

            $table->foreign('nip')->references('nip')->on('pegawai')->onDelete('cascade');
            $table->index('nip');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('riwayat_diklat');
    }
};
