<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cau_hinh_tai_khoan_thanh_toan', function (Blueprint $table) {
            $table->string('trang_thai', 30)
                ->default('dang_hoat_dong')
                ->after('mac_dinh');
        });
    }

    public function down(): void
    {
        Schema::table('cau_hinh_tai_khoan_thanh_toan', function (Blueprint $table) {
            $table->dropColumn('trang_thai');
        });
    }
};
