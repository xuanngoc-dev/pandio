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
        Schema::create('cau_hinh_thong_tin_studio', function (Blueprint $table) {
            $table->id();
            $table->string('ten_studio');
            $table->string('khau_hieu')->nullable();
            $table->string('logo')->nullable();
            $table->string('dia_chi')->nullable();
            $table->string('email')->nullable();
            $table->string('so_dien_thoai')->nullable();
            $table->string('ma_so_thue')->nullable();
            $table->string('mac_dinh')->default('khong'); // co | khong
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cau_hinh_thong_tin_studio');
    }
};
