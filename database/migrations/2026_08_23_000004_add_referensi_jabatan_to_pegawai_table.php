<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kolom tambahan hasil pencocokan pegawais_202608020842.csv (via NAMA, karena
 * kolom NIP di file itu rusak notasi ilmiah) ke master_jabfung.csv/master_genpos.csv/
 * master_unor.csv. Best-effort -- cuma terisi untuk pegawai yang namanya cocok unik
 * di kedua sisi, sisanya tetap null.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pegawai', function (Blueprint $table) {
            $table->string('jabatan_fungsional_spesifik')->nullable()->after('level_3');
            $table->unsignedSmallInteger('kelas_jabatan_fungsional')->nullable()->after('jabatan_fungsional_spesifik');
            $table->string('jabatan_umum_label')->nullable()->after('kelas_jabatan_fungsional');
            $table->string('unor_detail')->nullable()->after('jabatan_umum_label');
        });
    }

    public function down(): void
    {
        Schema::table('pegawai', function (Blueprint $table) {
            $table->dropColumn(['jabatan_fungsional_spesifik', 'kelas_jabatan_fungsional', 'jabatan_umum_label', 'unor_detail']);
        });
    }
};
