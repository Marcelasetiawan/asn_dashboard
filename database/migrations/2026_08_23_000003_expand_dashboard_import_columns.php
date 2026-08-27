<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pegawai', function (Blueprint $table) {
            $table->unsignedSmallInteger('umur')->nullable()->after('jenis_kelamin');
            $table->string('kelompok_opd')->nullable()->after('satuan_kerja');
            $table->string('opd_induk')->nullable()->after('kelompok_opd');
            $table->string('unor_nama')->nullable()->after('opd_induk');
            $table->string('instansi_kerja')->nullable()->after('unor_nama');
            $table->text('alamat')->nullable()->after('instansi_kerja');
            $table->string('email')->nullable()->after('alamat');
            $table->string('kategori_asn', 40)->nullable()->after('email');
            $table->string('level_1', 100)->nullable()->after('kategori_asn');
            $table->string('level_2', 100)->nullable()->after('level_1');
            $table->string('level_3', 100)->nullable()->after('level_2');

            $table->index('kategori_asn');
            $table->index('opd_induk');
        });

        Schema::table('riwayat_diklat', function (Blueprint $table) {
            $table->string('sumber', 40)->nullable()->after('nip');
            $table->string('bkn_id', 40)->nullable()->after('sumber');
            $table->date('tanggal_mulai')->nullable()->after('pelaksanaan');
            $table->date('tanggal_selesai')->nullable()->after('tanggal_mulai');
            $table->unsignedSmallInteger('tahun')->nullable()->after('tanggal_selesai');

            $table->index('sumber');
            $table->index('tahun');
        });
    }

    public function down(): void
    {
        Schema::table('riwayat_diklat', function (Blueprint $table) {
            $table->dropIndex(['sumber']);
            $table->dropIndex(['tahun']);
            $table->dropColumn(['sumber', 'bkn_id', 'tanggal_mulai', 'tanggal_selesai', 'tahun']);
        });

        Schema::table('pegawai', function (Blueprint $table) {
            $table->dropIndex(['kategori_asn']);
            $table->dropIndex(['opd_induk']);
            $table->dropColumn([
                'umur', 'kelompok_opd', 'opd_induk', 'unor_nama', 'instansi_kerja',
                'alamat', 'email', 'kategori_asn', 'level_1', 'level_2', 'level_3',
            ]);
        });
    }
};
