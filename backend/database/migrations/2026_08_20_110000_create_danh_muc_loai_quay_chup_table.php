<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('danh_muc_loai_quay_chup', function (Blueprint $table) {
            $table->id();
            $table->string('ten_dich_vu');
            $table->text('ghi_chu')->nullable();
            $table->string('trang_thai')->default('active');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('danh_muc_loai_quay_chup');
    }
};
