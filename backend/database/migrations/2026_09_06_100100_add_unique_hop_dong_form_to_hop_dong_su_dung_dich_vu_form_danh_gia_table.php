<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Unique đã có trong migration create bảng.
     * Migration này chỉ thêm nếu index chưa tồn tại (tránh Duplicate key trên production).
     */
    public function up(): void
    {
        if (! Schema::hasTable('hop_dong_su_dung_dich_vu_form_danh_gia')) {
            return;
        }

        $exists = DB::selectOne(
            'SELECT 1 AS ok FROM information_schema.statistics
             WHERE table_schema = DATABASE()
               AND table_name = ?
               AND index_name = ?
             LIMIT 1',
            ['hop_dong_su_dung_dich_vu_form_danh_gia', 'hdd_sddv_form_dg_hop_dong_form_unique']
        );

        if ($exists) {
            return;
        }

        Schema::table('hop_dong_su_dung_dich_vu_form_danh_gia', function (Blueprint $table) {
            $table->unique(
                ['hop_dong_danh_gia_id', 'form_danh_gia_id'],
                'hdd_sddv_form_dg_hop_dong_form_unique'
            );
        });
    }

    public function down(): void
    {
        // Unique thuộc migration create — không drop ở đây.
    }
};
