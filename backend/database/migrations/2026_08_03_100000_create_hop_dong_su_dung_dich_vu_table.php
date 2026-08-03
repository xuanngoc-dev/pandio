<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hop_dong_su_dung_dich_vu', function (Blueprint $table) {
            $table->id();
            $table->string('ma_hop_dong')->unique();
            $table->foreignId('loai_hop_dong_id')
                ->constrained('loai_hop_dong')
                ->restrictOnDelete();
            $table->string('kenh_tiep_can')->nullable();
            $table->json('thong_tin_hop_dong')->nullable();
            $table->foreignId('nguoi_tao_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->json('nguoi_tham_gia_ids')->nullable();
            $table->enum('trang_thai', [
                'moi_tao',
                'nhap',
                'da_coc',
                'dang_thuc_hien',
                'da_huy',
                'hoan_thanh',
            ])->default('moi_tao');
            $table->unsignedBigInteger('tong_tien')->default(0);
            $table->unsignedBigInteger('chiet_khau')->default(0);
            $table->string('ma_giam_gia')->nullable();
            $table->unsignedBigInteger('khuyen_mai_theo_ma_giam_gia')->default(0);
            $table->unsignedBigInteger('tien_coc')->default(0);
            $table->enum('hinh_thuc_coc', ['online', 'offline'])->nullable();
            $table->date('han_thanh_toan_lan_2')->nullable();
            $table->date('han_thanh_toan_lan_3')->nullable();
            $table->string('qua_tang_kem')->nullable();
            $table->text('yeu_cau_dac_biet')->nullable();
            $table->string('luot_gioi_thieu')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hop_dong_su_dung_dich_vu');
    }
};
