<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dich_vu_danh_sach_dich_vu_le', function (Blueprint $table) {
            $table->id();
            $table->string('ma_dich_vu')->unique();
            $table->string('ten_dich_vu');
            $table->foreignId('loai_dich_vu_id')
                ->constrained('dich_vu_loai_dich_vu')
                ->restrictOnDelete();
            $table->json('loai_hop_dong_ids')->nullable();
            $table->decimal('gia_goc', 15, 0)->default(0);
            $table->decimal('gia_khuyen_mai', 15, 0)->nullable();
            $table->text('mo_ta')->nullable();
            $table->enum('trang_thai', ['dang_su_dung', 'ngung_su_dung'])->default('dang_su_dung');
            $table->text('ghi_chu')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dich_vu_danh_sach_dich_vu_le');
    }
};
