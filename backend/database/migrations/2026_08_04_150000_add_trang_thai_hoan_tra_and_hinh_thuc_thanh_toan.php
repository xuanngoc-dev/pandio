<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hop_dong_cho_thue_trang_phuc_san_pham_cho_thue', function (Blueprint $table) {
            $table->string('trang_thai_hoan_tra', 30)
                ->default('chua_hoan_tra')
                ->after('ghi_chu');
        });

        Schema::table('hop_dong_cho_thue_trang_phuc', function (Blueprint $table) {
            $table->string('hinh_thuc_thanh_toan', 50)
                ->nullable()
                ->after('tien_coc');
        });
    }

    public function down(): void
    {
        Schema::table('hop_dong_cho_thue_trang_phuc_san_pham_cho_thue', function (Blueprint $table) {
            $table->dropColumn('trang_thai_hoan_tra');
        });

        Schema::table('hop_dong_cho_thue_trang_phuc', function (Blueprint $table) {
            $table->dropColumn('hinh_thuc_thanh_toan');
        });
    }
};
