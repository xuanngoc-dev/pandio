<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trang_phuc', function (Blueprint $table) {
            $table->dropConstrainedForeignId('chi_nhanh');
        });
    }

    public function down(): void
    {
        Schema::table('trang_phuc', function (Blueprint $table) {
            $table->foreignId('chi_nhanh')
                ->nullable()
                ->after('nha_cung_cap')
                ->constrained('cau_hinh_chi_nhanh')
                ->nullOnDelete();
        });
    }
};
