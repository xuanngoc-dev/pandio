<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hop_dong_dong_sddv_dich_vu', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ma_hop_dong_id');
            $table->unsignedBigInteger('dich_vu_id');
            $table->unsignedInteger('so_luong')->default(1);
            $table->unsignedBigInteger('thanh_tien')->default(0);
            $table->text('ghi_chu')->nullable();
            $table->timestamps();

            $table->foreign('ma_hop_dong_id', 'hdd_sddv_dich_vu_hop_dong_fk')
                ->references('id')
                ->on('hop_dong_su_dung_dich_vu')
                ->cascadeOnDelete();
            $table->foreign('dich_vu_id', 'hdd_sddv_dich_vu_dich_vu_fk')
                ->references('id')
                ->on('dich_vu_danh_sach_dich_vu_le')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hop_dong_dong_sddv_dich_vu');
    }
};
