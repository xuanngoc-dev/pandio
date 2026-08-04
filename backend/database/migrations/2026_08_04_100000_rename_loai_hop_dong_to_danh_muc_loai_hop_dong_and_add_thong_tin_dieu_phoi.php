<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::rename('loai_hop_dong', 'danh_muc_loai_hop_dong');

        Schema::table('danh_muc_loai_hop_dong', function (Blueprint $table) {
            $table->json('thong_tin_dieu_phoi')->nullable()->after('noi_dung');
        });

        Schema::table('hop_dong_su_dung_dich_vu', function (Blueprint $table) {
            $table->json('thong_tin_dieu_phoi')->nullable()->after('thong_tin_hop_dong');
        });
    }

    public function down(): void
    {
        Schema::table('hop_dong_su_dung_dich_vu', function (Blueprint $table) {
            $table->dropColumn('thong_tin_dieu_phoi');
        });

        Schema::table('danh_muc_loai_hop_dong', function (Blueprint $table) {
            $table->dropColumn('thong_tin_dieu_phoi');
        });

        Schema::rename('danh_muc_loai_hop_dong', 'loai_hop_dong');
    }
};
