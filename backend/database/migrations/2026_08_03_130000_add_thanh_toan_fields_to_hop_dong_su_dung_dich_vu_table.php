<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hop_dong_su_dung_dich_vu', function (Blueprint $table) {
            $table->unsignedBigInteger('phat_sinh')->default(0)->after('tong_tien');
            $table->unsignedBigInteger('so_tien_thanh_toan_lan_1')->default(0)->after('tien_coc');
            $table->unsignedBigInteger('so_tien_thanh_toan_lan_2')->default(0)->after('so_tien_thanh_toan_lan_1');
            $table->unsignedBigInteger('so_tien_thanh_toan_lan_3')->default(0)->after('so_tien_thanh_toan_lan_2');
            $table->dateTime('thoi_gian_thanh_toan_lan_1')->nullable()->after('so_tien_thanh_toan_lan_3');
            $table->dateTime('thoi_gian_thanh_toan_lan_2')->nullable()->after('thoi_gian_thanh_toan_lan_1');
            $table->dateTime('thoi_gian_thanh_toan_lan_3')->nullable()->after('thoi_gian_thanh_toan_lan_2');
        });
    }

    public function down(): void
    {
        Schema::table('hop_dong_su_dung_dich_vu', function (Blueprint $table) {
            $table->dropColumn([
                'phat_sinh',
                'so_tien_thanh_toan_lan_1',
                'so_tien_thanh_toan_lan_2',
                'so_tien_thanh_toan_lan_3',
                'thoi_gian_thanh_toan_lan_1',
                'thoi_gian_thanh_toan_lan_2',
                'thoi_gian_thanh_toan_lan_3',
            ]);
        });
    }
};
