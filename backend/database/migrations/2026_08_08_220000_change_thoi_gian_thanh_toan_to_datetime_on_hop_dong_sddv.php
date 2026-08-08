<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Thời gian thanh toán lần 1/2/3 lưu cả ngày giờ (datetime).
     */
    public function up(): void
    {
        Schema::table('hop_dong_su_dung_dich_vu', function (Blueprint $table) {
            $table->dateTime('thoi_gian_thanh_toan_lan_1')->nullable()->change();
            $table->dateTime('thoi_gian_thanh_toan_lan_2')->nullable()->change();
            $table->dateTime('thoi_gian_thanh_toan_lan_3')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('hop_dong_su_dung_dich_vu', function (Blueprint $table) {
            $table->date('thoi_gian_thanh_toan_lan_1')->nullable()->change();
            $table->date('thoi_gian_thanh_toan_lan_2')->nullable()->change();
            $table->date('thoi_gian_thanh_toan_lan_3')->nullable()->change();
        });
    }
};
