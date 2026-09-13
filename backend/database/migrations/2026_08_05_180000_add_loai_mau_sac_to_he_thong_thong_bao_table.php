<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Cột đã có trong migration create bảng he_thong_thong_bao.
     * Chỉ thêm nếu chưa tồn tại.
     */
    public function up(): void
    {
        if (! Schema::hasTable('he_thong_thong_bao')) {
            return;
        }

        if (Schema::hasColumn('he_thong_thong_bao', 'loai_mau_sac')) {
            return;
        }

        Schema::table('he_thong_thong_bao', function (Blueprint $table) {
            $table->string('loai_mau_sac', 32)
                ->default('blue')
                ->after('loai_thong_bao_id');
        });
    }

    public function down(): void
    {
        // Cột thuộc migration create — không drop ở đây.
    }
};
