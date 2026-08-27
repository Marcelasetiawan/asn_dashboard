<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabel `pegawai` — kolomnya mengikuti persis header Excel Sheet1:
     * No.(diabaikan, pakai auto id) | NIP Baru | Nama | L/P | Status | Gol. Ruang |
     * TMT Pangkat | Eselon | Jabatan | TMT Jabatan | Agama | Satuan Kerja |
     * TMT Pensiun | Pendidikan | Lulus
     */
    public function up(): void
    {
        Schema::create('pegawai', function (Blueprint $table) {
            $table->string('nip', 30)->primary();      // "NIP Baru"
            $table->string('nama', 255);                // "Nama"
            $table->string('jenis_kelamin', 20)->nullable();   // "L/P"
            $table->string('status', 50)->nullable();          // "Status"
            $table->string('golongan_ruang', 50)->nullable();  // "Gol. Ruang"
            $table->string('tmt_pangkat', 20)->nullable();     // "TMT Pangkat"
            $table->string('eselon', 50)->nullable();          // "Eselon"
            $table->string('jabatan', 500)->nullable();        // "Jabatan"
            $table->string('tmt_jabatan', 20)->nullable();     // "TMT Jabatan"
            $table->string('agama', 20)->nullable();           // "Agama"
            $table->string('satuan_kerja', 500)->nullable();   // "Satuan Kerja"
            $table->string('tmt_pensiun', 20)->nullable();     // "TMT Pensiun"
            $table->string('pendidikan', 10)->nullable();      // "Pendidikan"
            $table->integer('tahun_lulus')->nullable();        // "Lulus"
            $table->timestamps();

            $table->index('satuan_kerja');
            $table->index('golongan_ruang');
            $table->index('nama');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pegawai');
    }
};
