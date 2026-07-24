<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trang_phuc', function (Blueprint $table) {
            $table->id();
            $table->string('hinh_anh')->nullable();
            $table->string('ma_san_pham')->unique();
            $table->string('ten_san_pham');
            $table->foreignId('danh_muc')
                ->constrained('danh_muc_trang_phuc')
                ->cascadeOnDelete();
            $table->foreignId('nha_cung_cap')
                ->constrained('nha_cung_cap_trang_phuc')
                ->cascadeOnDelete();
            $table->foreignId('chi_nhanh')
                ->constrained('cau_hinh_chi_nhanh')
                ->cascadeOnDelete();
            $table->unsignedBigInteger('gia_tri')->default(0);
            $table->unsignedBigInteger('gia_cho_thue')->default(0);
            $table->string('phan_loai_chi_phi', 50);
            $table->string('tinh_trang', 50);
            $table->text('ghi_chu')->nullable();
            $table->unsignedTinyInteger('trang_thai')->default(1);
            $table->json('thong_tin_them')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trang_phuc');
    }
};
