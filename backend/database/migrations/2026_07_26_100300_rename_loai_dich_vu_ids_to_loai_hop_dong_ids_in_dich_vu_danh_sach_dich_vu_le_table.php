<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Cột đã là loai_hop_dong_ids trong migration create bảng.
     * Chỉ rename nếu cột cũ còn tồn tại.
     */
    public function up(): void
    {
        if (! Schema::hasTable('dich_vu_danh_sach_dich_vu_le')) {
            return;
        }

        if (Schema::hasColumn('dich_vu_danh_sach_dich_vu_le', 'loai_hop_dong_ids')) {
            return;
        }

        if (! Schema::hasColumn('dich_vu_danh_sach_dich_vu_le', 'loai_dich_vu_ids')) {
            return;
        }

        Schema::table('dich_vu_danh_sach_dich_vu_le', function (Blueprint $table) {
            $table->renameColumn('loai_dich_vu_ids', 'loai_hop_dong_ids');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('dich_vu_danh_sach_dich_vu_le')) {
            return;
        }

        if (Schema::hasColumn('dich_vu_danh_sach_dich_vu_le', 'loai_dich_vu_ids')) {
            return;
        }

        if (! Schema::hasColumn('dich_vu_danh_sach_dich_vu_le', 'loai_hop_dong_ids')) {
            return;
        }

        Schema::table('dich_vu_danh_sach_dich_vu_le', function (Blueprint $table) {
            $table->renameColumn('loai_hop_dong_ids', 'loai_dich_vu_ids');
        });
    }
};
