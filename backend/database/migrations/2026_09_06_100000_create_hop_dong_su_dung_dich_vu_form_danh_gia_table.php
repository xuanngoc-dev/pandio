<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hop_dong_su_dung_dich_vu_form_danh_gia', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('hop_dong_danh_gia_id');
            $table->unsignedBigInteger('form_danh_gia_id');
            $table->json('noi_dung_danh_gia')->nullable();
            $table->timestamps();

            $table->foreign('hop_dong_danh_gia_id', 'hdd_sddv_form_dg_hop_dong_fk')
                ->references('id')
                ->on('hop_dong_su_dung_dich_vu')
                ->cascadeOnDelete();

            $table->foreign('form_danh_gia_id', 'hdd_sddv_form_dg_form_fk')
                ->references('id')
                ->on('cau_hinh_form_danh_gia_mau')
                ->restrictOnDelete();

            $table->unique(
                ['hop_dong_danh_gia_id', 'form_danh_gia_id'],
                'hdd_sddv_form_dg_hop_dong_form_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hop_dong_su_dung_dich_vu_form_danh_gia');
    }
};
