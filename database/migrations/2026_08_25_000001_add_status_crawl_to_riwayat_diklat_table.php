<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * "Status_Crawl" dari sumber sertifikasi asn.xlsx -- menandai apakah
     * data sertifikat baris ini berhasil diambil ("Sukses") atau memang
     * tidak ada catatan sertifikasinya di sumber ("Tidak Ada Sertifikasi").
     * Cuma terisi untuk riwayat yang sumbernya 'sertifikasi_asn', null
     * untuk sumber lain (diklat_siasn, hasil_akhir_modul, dst).
     */
    public function up(): void
    {
        Schema::table('riwayat_diklat', function (Blueprint $table) {
            $table->string('status_crawl', 60)->nullable()->after('bkn_id');
        });
    }

    public function down(): void
    {
        Schema::table('riwayat_diklat', function (Blueprint $table) {
            $table->dropColumn('status_crawl');
        });
    }
};
