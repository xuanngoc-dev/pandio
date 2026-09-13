<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('loai_hop_dong') && ! Schema::hasTable('danh_muc_loai_hop_dong')) {
            Schema::rename('loai_hop_dong', 'danh_muc_loai_hop_dong');
        }

        if (Schema::hasTable('danh_muc_loai_hop_dong')
            && ! Schema::hasColumn('danh_muc_loai_hop_dong', 'thong_tin_dieu_phoi')) {
            Schema::table('danh_muc_loai_hop_dong', function (Blueprint $table) {
                $after = Schema::hasColumn('danh_muc_loai_hop_dong', 'noi_dung')
                    ? 'noi_dung'
                    : null;

                $column = $table->json('thong_tin_dieu_phoi')->nullable();
                if ($after !== null) {
                    $column->after($after);
                }
            });
        }

        if (Schema::hasTable('hop_dong_su_dung_dich_vu')
            && ! Schema::hasColumn('hop_dong_su_dung_dich_vu', 'thong_tin_dieu_phoi')) {
            Schema::table('hop_dong_su_dung_dich_vu', function (Blueprint $table) {
                $after = Schema::hasColumn('hop_dong_su_dung_dich_vu', 'thong_tin_hop_dong')
                    ? 'thong_tin_hop_dong'
                    : null;

                $column = $table->json('thong_tin_dieu_phoi')->nullable();
                if ($after !== null) {
                    $column->after($after);
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('hop_dong_su_dung_dich_vu')
            && Schema::hasColumn('hop_dong_su_dung_dich_vu', 'thong_tin_dieu_phoi')) {
            Schema::table('hop_dong_su_dung_dich_vu', function (Blueprint $table) {
                $table->dropColumn('thong_tin_dieu_phoi');
            });
        }

        if (Schema::hasTable('danh_muc_loai_hop_dong')
            && Schema::hasColumn('danh_muc_loai_hop_dong', 'thong_tin_dieu_phoi')) {
            Schema::table('danh_muc_loai_hop_dong', function (Blueprint $table) {
                $table->dropColumn('thong_tin_dieu_phoi');
            });
        }

        if (Schema::hasTable('danh_muc_loai_hop_dong') && ! Schema::hasTable('loai_hop_dong')) {
            Schema::rename('danh_muc_loai_hop_dong', 'loai_hop_dong');
        }
    }
};
