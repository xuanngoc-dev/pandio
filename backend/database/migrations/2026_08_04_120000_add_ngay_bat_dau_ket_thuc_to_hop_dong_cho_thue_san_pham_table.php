<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hop_dong_cho_thue_trang_phuc_san_pham_cho_thue', function (Blueprint $table) {
            $table->date('ngay_bat_dau')->nullable()->after('san_pham_id');
            $table->date('ngay_ket_thuc')->nullable()->after('ngay_bat_dau');
        });
    }

    public function down(): void
    {
        Schema::table('hop_dong_cho_thue_trang_phuc_san_pham_cho_thue', function (Blueprint $table) {
            $table->dropColumn(['ngay_bat_dau', 'ngay_ket_thuc']);
        });
    }
};
