<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dich_vu_danh_sach_dich_vu_le', function (Blueprint $table) {
            $table->renameColumn('loai_dich_vu_ids', 'loai_hop_dong_ids');
        });
    }

    public function down(): void
    {
        Schema::table('dich_vu_danh_sach_dich_vu_le', function (Blueprint $table) {
            $table->renameColumn('loai_hop_dong_ids', 'loai_dich_vu_ids');
        });
    }
};
