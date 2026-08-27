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
        Schema::table('users', function (Blueprint $table) {
            // "username" = identitas login: NIP untuk role asn, bebas untuk admin
            // (bukan email, karena cuma ~1/3 pegawai yang punya data email).
            $table->string('username')->unique()->after('id');
            $table->string('role', 20)->default('asn')->after('username');
            // Nullable karena admin tidak punya baris di tabel pegawai.
            $table->string('nip', 32)->nullable()->unique()->after('role');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['username', 'role', 'nip']);
        });
    }
};
