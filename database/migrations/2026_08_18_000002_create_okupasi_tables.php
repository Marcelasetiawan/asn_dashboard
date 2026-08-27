<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabel referensi Peta Okupasi Nasional bidang TIK (dari
 * Peta_Okupasi_PON_TIK_expanded.xlsx) + pemetaan jabatan ASN -> okupasi.
 *
 * - okupasi            : 234 okupasi (Area Fungsi, Nama Okupasi, Kualifikasi, dst)
 * - okupasi_tugas      : daftar tugas/modul pelatihan tiap okupasi (1 okupasi
 *                        punya banyak tugas -- inilah yang jadi "pilihan
 *                        pelatihan" di halaman Rekomendasi Pelatihan)
 * - jabatan_okupasi_mapping : jabatan ASN -> okupasi yang paling relevan.
 *                        okupasi_id NULL = jabatan itu belum dipetakan/bukan TIK.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('okupasi', function (Blueprint $table) {
            $table->id();
            $table->string('kode')->nullable();
            $table->string('area');
            $table->string('nama');
            $table->string('kualifikasi')->nullable();
            $table->text('definisi')->nullable();
            $table->string('sertifikasi')->nullable();
            $table->timestamps();
        });

        Schema::create('okupasi_tugas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('okupasi_id')->constrained('okupasi')->onDelete('cascade');
            // text (bukan string/VARCHAR 255) -- sebagian "Tugas Utama" di
            // sumber datanya berisi beberapa kalimat sekaligus dalam 1 sel,
            // jadi bisa jauh lebih panjang dari 255 karakter.
            $table->text('nama');
            $table->string('kode_standar')->nullable();
            $table->timestamps();
        });

        Schema::create('jabatan_okupasi_mapping', function (Blueprint $table) {
            $table->id();
            $table->string('jabatan')->unique();
            $table->foreignId('okupasi_id')->nullable()->constrained('okupasi')->onDelete('set null');
            $table->decimal('skor', 5, 4)->nullable();
            $table->string('sumber')->default('auto_tfidf_placeholder'); // 'auto_tfidf_placeholder' | 'manual'
            $table->string('catatan')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jabatan_okupasi_mapping');
        Schema::dropIfExists('okupasi_tugas');
        Schema::dropIfExists('okupasi');
    }
};
