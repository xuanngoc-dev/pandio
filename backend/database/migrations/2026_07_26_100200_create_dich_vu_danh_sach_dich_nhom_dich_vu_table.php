<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dich_vu_danh_sach_dich_nhom_dich_vu', function (Blueprint $table) {
            $table->id();
            $table->string('ma_nhom')->unique();
            $table->string('ten_nhom');
            $table->decimal('gia_goc', 15, 0)->default(0);
            $table->decimal('gia_khuyen_mai', 15, 0)->nullable();
            $table->foreignId('loai_dich_vu_id')
                ->constrained('dich_vu_loai_dich_vu')
                ->restrictOnDelete();
            $table->unsignedInteger('so_diem_chup')->default(0);
            $table->unsignedInteger('so_anh_chinh_sua')->default(0);
            $table->json('dich_vu_le_ids')->nullable();
            $table->enum('trang_thai', ['dang_su_dung', 'ngung_su_dung'])->default('dang_su_dung');
            $table->text('ghi_chu')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dich_vu_danh_sach_dich_nhom_dich_vu');
    }
};
