<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('riwayat_diklat', function (Blueprint $table) {
            // Path file berkas sertifikat (disk "public"), diisi lewat
            // fitur unggah sertifikat mandiri di halaman "Profil Saya" (ASN).
            $table->string('berkas_sertifikat')->nullable()->after('no_sertifikat');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('riwayat_diklat', function (Blueprint $table) {
            $table->dropColumn('berkas_sertifikat');
        });
    }
};
