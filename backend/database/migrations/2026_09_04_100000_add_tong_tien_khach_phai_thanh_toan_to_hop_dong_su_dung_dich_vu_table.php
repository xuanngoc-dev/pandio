<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('hop_dong_su_dung_dich_vu')) {
            return;
        }

        if (! Schema::hasColumn('hop_dong_su_dung_dich_vu', 'tong_tien_khach_phai_thanh_toan')) {
            Schema::table('hop_dong_su_dung_dich_vu', function (Blueprint $table) {
                $column = $table->unsignedBigInteger('tong_tien_khach_phai_thanh_toan')->default(0);
                if (Schema::hasColumn('hop_dong_su_dung_dich_vu', 'khuyen_mai_theo_ma_giam_gia')) {
                    $column->after('khuyen_mai_theo_ma_giam_gia');
                }
            });
        }

        // tong_tien + phat_sinh - chiet_khau - khuyen_mai_theo_ma_giam_gia
        DB::table('hop_dong_su_dung_dich_vu')->update([
            'tong_tien_khach_phai_thanh_toan' => DB::raw(
                'GREATEST(0, CAST(tong_tien AS SIGNED) + CAST(phat_sinh AS SIGNED) - CAST(chiet_khau AS SIGNED) - CAST(khuyen_mai_theo_ma_giam_gia AS SIGNED))'
            ),
        ]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('hop_dong_su_dung_dich_vu')
            || ! Schema::hasColumn('hop_dong_su_dung_dich_vu', 'tong_tien_khach_phai_thanh_toan')) {
            return;
        }

        Schema::table('hop_dong_su_dung_dich_vu', function (Blueprint $table) {
            $table->dropColumn('tong_tien_khach_phai_thanh_toan');
        });
    }
};
