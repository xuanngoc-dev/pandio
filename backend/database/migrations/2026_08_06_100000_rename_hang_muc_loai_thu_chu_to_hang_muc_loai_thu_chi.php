<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Đổi tên bảng hang_muc_loai_thu_chu → hang_muc_loai_thu_chi (cho DB đã migrate trước đó).
 *
 * Trường hợp file create đã đổi tên: Laravel có thể tạo bảng chi rỗng song song với chu.
 * Migration này ưu tiên dữ liệu bảng chu, xóa chi rỗng rồi rename.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('hang_muc_loai_thu_chu')) {
            return;
        }

        if (Schema::hasTable('hang_muc_loai_thu_chi')) {
            Schema::drop('hang_muc_loai_thu_chi');
        }

        if (Schema::hasTable('phieu_thu_chi')) {
            Schema::table('phieu_thu_chi', function (Blueprint $table) {
                $table->dropForeign(['hang_muc_id']);
            });
        }

        Schema::rename('hang_muc_loai_thu_chu', 'hang_muc_loai_thu_chi');

        if (Schema::hasTable('phieu_thu_chi')) {
            Schema::table('phieu_thu_chi', function (Blueprint $table) {
                $table->foreign('hang_muc_id')
                    ->references('id')
                    ->on('hang_muc_loai_thu_chi')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('hang_muc_loai_thu_chi') || Schema::hasTable('hang_muc_loai_thu_chu')) {
            return;
        }

        if (Schema::hasTable('phieu_thu_chi')) {
            Schema::table('phieu_thu_chi', function (Blueprint $table) {
                $table->dropForeign(['hang_muc_id']);
            });
        }

        Schema::rename('hang_muc_loai_thu_chi', 'hang_muc_loai_thu_chu');

        if (Schema::hasTable('phieu_thu_chi')) {
            Schema::table('phieu_thu_chi', function (Blueprint $table) {
                $table->foreign('hang_muc_id')
                    ->references('id')
                    ->on('hang_muc_loai_thu_chu')
                    ->nullOnDelete();
            });
        }
    }
};
