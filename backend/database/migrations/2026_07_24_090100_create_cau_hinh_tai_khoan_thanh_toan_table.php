<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('cau_hinh_tai_khoan_thanh_toan', function (Blueprint $table) {
            $table->id();
            $table->string('ngan_hang');
            $table->string('so_tai_khoan');
            $table->string('chu_tai_khoan');
            $table->string('chi_nhanh')->nullable();
            $table->string('mac_dinh')->default('khong'); // co | khong
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cau_hinh_tai_khoan_thanh_toan');
    }
};
