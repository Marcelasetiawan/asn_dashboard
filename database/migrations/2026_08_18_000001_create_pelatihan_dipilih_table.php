<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabel penyimpan pilihan pelatihan yang dipilih tiap pegawai, dari daftar
 * rekomendasi pelatihan (hasil pencocokan jabatan -> okupasi TIK -> daftar
 * modul pelatihan/tugas okupasi tersebut).
 *
 * Satu pegawai bisa punya banyak baris di sini (dia boleh pilih lebih dari
 * satu pelatihan dari daftar yang direkomendasikan).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pelatihan_dipilih', function (Blueprint $table) {
            $table->id();
            $table->string('nip', 32);
            $table->string('nama_okupasi');
            $table->string('nama_pelatihan');
            $table->string('kode_standar')->nullable();
            $table->timestamp('dipilih_pada')->useCurrent();
            $table->timestamps();

            $table->foreign('nip')->references('nip')->on('pegawai')->onDelete('cascade');
            $table->unique(['nip', 'nama_pelatihan'], 'pelatihan_dipilih_nip_pelatihan_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pelatihan_dipilih');
    }
};
