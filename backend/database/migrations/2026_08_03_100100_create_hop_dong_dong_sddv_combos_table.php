<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hop_dong_dong_sddv_combos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ma_hop_dong_id');
            $table->unsignedBigInteger('combo_id');
            $table->unsignedInteger('so_luong')->default(1);
            $table->unsignedBigInteger('thanh_tien')->default(0);
            $table->text('ghi_chu')->nullable();
            $table->timestamps();

            $table->foreign('ma_hop_dong_id', 'hdd_sddv_combos_hop_dong_fk')
                ->references('id')
                ->on('hop_dong_su_dung_dich_vu')
                ->cascadeOnDelete();
            $table->foreign('combo_id', 'hdd_sddv_combos_combo_fk')
                ->references('id')
                ->on('dich_vu_danh_sach_dich_nhom_dich_vu')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hop_dong_dong_sddv_combos');
    }
};
