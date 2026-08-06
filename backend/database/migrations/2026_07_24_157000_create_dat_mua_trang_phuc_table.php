<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dat_mua_trang_phuc', function (Blueprint $table) {
            $table->id();
            $table->foreignId('nha_cung_cap_id')
                ->constrained('nha_cung_cap_trang_phuc')
                ->cascadeOnDelete();
            $table->string('loai_don_hang', 50);
            $table->string('nguon_hang_hoa', 50);
            $table->date('ngay_dat');
            $table->json('mat_hang');
            $table->unsignedBigInteger('tong_tien_hang')->default(0);
            $table->unsignedBigInteger('phi_van_chuyen')->default(0);
            $table->unsignedBigInteger('tien_coc')->default(0);
            $table->bigInteger('du_no')->default(0);
            $table->enum('trang_thai', ['cho_duyet', 'da_duyet', 'huy_duyet'])->default('cho_duyet');
            $table->timestamps();

            $table->index('trang_thai');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dat_mua_trang_phuc');
    }
};
