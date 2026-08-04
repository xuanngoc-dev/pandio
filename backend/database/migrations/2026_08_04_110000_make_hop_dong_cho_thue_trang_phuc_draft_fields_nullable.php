<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hop_dong_cho_thue_trang_phuc', function (Blueprint $table) {
            $table->string('ten_khach_hang')->nullable()->change();
            $table->string('sdt_khach_hang', 20)->nullable()->change();
            $table->date('ngay_thue')->nullable()->change();
            $table->date('ngay_tra_du_kien')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('hop_dong_cho_thue_trang_phuc', function (Blueprint $table) {
            $table->string('ten_khach_hang')->nullable(false)->change();
            $table->string('sdt_khach_hang', 20)->nullable(false)->change();
            $table->date('ngay_thue')->nullable(false)->change();
            $table->date('ngay_tra_du_kien')->nullable(false)->change();
        });
    }
};
