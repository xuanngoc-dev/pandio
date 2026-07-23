<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('nhan_vien', function (Blueprint $table) {
            $table->id();
            $table->string('hinh_anh')->nullable();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('phong_ban_id')->nullable()->constrained('phong_ban')->nullOnDelete();
            $table->string('ngan_hang')->nullable();
            $table->string('chi_nhanh')->nullable();
            $table->string('so_tai_khoan')->nullable();
            $table->string('chu_tai_khoan')->nullable();
            $table->string('gioi_tinh', 20)->nullable();
            $table->date('ngay_sinh')->nullable();
            $table->string('cccd', 20)->nullable()->unique();
            $table->string('vi_tri_lam_viec')->nullable();
            $table->date('ngay_vao_cong_ty')->nullable();
            $table->date('ngay_ky_hop_dong')->nullable();
            $table->enum('loai_nhan_vien', ['part_time', 'full_time'])->nullable();
            $table->enum('loai_hop_dong', ['chinh_thuc', 'hoc_viec', 'thu_viec'])->nullable();
            $table->decimal('cong_chuan', 8, 2)->nullable();
            $table->boolean('tham_gia_bao_hiem')->default(false);
            $table->unsignedTinyInteger('so_nguoi_phu_thuoc')->default(0);
            $table->decimal('luong_cung', 15, 2)->nullable();
            $table->decimal('luong_mem', 15, 2)->nullable();
            $table->decimal('phu_cap', 15, 2)->nullable();
            $table->decimal('luong_co_ban', 15, 2)->nullable();
            $table->decimal('luong_tang_ca', 15, 2)->nullable();
            $table->decimal('phu_cap_xang', 15, 2)->nullable();
            $table->decimal('phu_cap_an_trua', 15, 2)->nullable();
            $table->decimal('phu_cap_dien_thoai', 15, 2)->nullable();
            $table->decimal('phu_cap_nha_o', 15, 2)->nullable();
            $table->decimal('thuong_chuyen_can', 15, 2)->nullable();
            $table->decimal('hoa_hong_hop_dong_cuoi', 15, 2)->nullable();
            $table->decimal('hoa_hong_hop_dong_trang_phuc', 15, 2)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nhan_vien');
    }
};
