<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dich_vu_danh_sach_dich_nhom_dich_vu', function (Blueprint $table) {
            $table->dropForeign(['loai_dich_vu_id']);
        });

        Schema::table('dich_vu_danh_sach_dich_nhom_dich_vu', function (Blueprint $table) {
            $table->renameColumn('loai_dich_vu_id', 'loai_hop_dong_id');
        });

        Schema::table('dich_vu_danh_sach_dich_nhom_dich_vu', function (Blueprint $table) {
            $table->foreign('loai_hop_dong_id')
                ->references('id')
                ->on('loai_hop_dong')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('dich_vu_danh_sach_dich_nhom_dich_vu', function (Blueprint $table) {
            $table->dropForeign(['loai_hop_dong_id']);
        });

        Schema::table('dich_vu_danh_sach_dich_nhom_dich_vu', function (Blueprint $table) {
            $table->renameColumn('loai_hop_dong_id', 'loai_dich_vu_id');
        });

        Schema::table('dich_vu_danh_sach_dich_nhom_dich_vu', function (Blueprint $table) {
            $table->foreign('loai_dich_vu_id')
                ->references('id')
                ->on('dich_vu_loai_dich_vu')
                ->restrictOnDelete();
        });
    }
};
