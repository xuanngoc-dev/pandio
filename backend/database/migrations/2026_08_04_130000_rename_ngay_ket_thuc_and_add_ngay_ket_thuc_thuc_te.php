<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hop_dong_cho_thue_trang_phuc_san_pham_cho_thue', function (Blueprint $table) {
            $table->renameColumn('ngay_ket_thuc', 'ngay_ket_thuc_du_kien');
        });

        Schema::table('hop_dong_cho_thue_trang_phuc_san_pham_cho_thue', function (Blueprint $table) {
            $table->date('ngay_ket_thuc_thuc_te')->nullable()->after('ngay_ket_thuc_du_kien');
        });
    }

    public function down(): void
    {
        Schema::table('hop_dong_cho_thue_trang_phuc_san_pham_cho_thue', function (Blueprint $table) {
            $table->dropColumn('ngay_ket_thuc_thuc_te');
        });

        Schema::table('hop_dong_cho_thue_trang_phuc_san_pham_cho_thue', function (Blueprint $table) {
            $table->renameColumn('ngay_ket_thuc_du_kien', 'ngay_ket_thuc');
        });
    }
};
