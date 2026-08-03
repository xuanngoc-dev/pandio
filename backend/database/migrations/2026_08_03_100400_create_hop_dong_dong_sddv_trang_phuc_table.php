<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hop_dong_dong_sddv_trang_phuc', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ma_hop_dong_id');
            $table->unsignedBigInteger('trang_phuc_id');
            $table->date('ngay_bat_dau')->nullable();
            $table->date('ngay_ket_thuc')->nullable();
            $table->timestamps();

            $table->foreign('ma_hop_dong_id', 'hdd_sddv_trang_phuc_hop_dong_fk')
                ->references('id')
                ->on('hop_dong_su_dung_dich_vu')
                ->cascadeOnDelete();
            $table->foreign('trang_phuc_id', 'hdd_sddv_trang_phuc_trang_phuc_fk')
                ->references('id')
                ->on('trang_phuc')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hop_dong_dong_sddv_trang_phuc');
    }
};
