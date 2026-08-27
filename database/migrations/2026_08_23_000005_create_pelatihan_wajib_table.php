<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Daftar pelatihan WAJIB per kategori jabatan (pegawai.jabatan, 4 nilai:
 * Jabatan Pelaksana/Jabatan Fungsional/Jabatan Fungsional Umum/Jabatan
 * Struktural) x level (Dasar/Menengah/Tinggi). Isinya statis, diseed lewat
 * PelatihanWajibSeeder -- kalau kurikulumnya perlu direvisi, edit array di
 * seeder itu.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pelatihan_wajib', function (Blueprint $table) {
            $table->id();
            $table->string('kategori_jabatan');
            $table->string('level', 20);
            $table->string('nama_pelatihan');
            $table->unsignedSmallInteger('urutan')->default(0);
            $table->timestamps();

            $table->index('kategori_jabatan');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pelatihan_wajib');
    }
};
