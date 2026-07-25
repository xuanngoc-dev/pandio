<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hop_dong_cho_thue_trang_phuc', function (Blueprint $table) {
            $table->id();
            $table->string('ma_hop_dong')->unique();
            $table->string('ten_khach_hang');
            $table->string('sdt_khach_hang', 20);
            $table->date('ngay_thue');
            $table->date('ngay_tra_du_kien');
            $table->date('ngay_tra_chinh_thuc')->nullable();
            $table->unsignedInteger('so_ngay_thue')->default(1);
            $table->unsignedBigInteger('tong_tien')->default(0);
            $table->unsignedBigInteger('giam_gia')->default(0);
            $table->unsignedBigInteger('tien_coc')->default(0);
            $table->string('trang_thai', 50)->default('cho_xac_nhan');
            $table->foreignId('nguoi_cho_thue')->nullable()->constrained('users')->nullOnDelete();
            $table->json('nguoi_tham_gia')->nullable();
            $table->text('ghi_chu_sale')->nullable();
            $table->text('ghi_chu_khach')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hop_dong_cho_thue_trang_phuc');
    }
};
