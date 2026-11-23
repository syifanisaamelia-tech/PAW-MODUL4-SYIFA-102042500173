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
        Schema::table('buku', function (Blueprint $table) {
            // Menambahkan kolom 'cover' bertipe string, boleh null, setelah kolom 'stok'
            if (!Schema::hasColumn('buku', 'cover')) {
                $table->string('cover')->nullable()->after('stok');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('buku', function (Blueprint $table) {
            // Menghapus kolom 'cover' saat rollback
            if (Schema::hasColumn('buku', 'cover')) {
                $table->dropColumn('cover');
            }
        });
    }
};