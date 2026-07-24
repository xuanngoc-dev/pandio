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
        Schema::create('cau_hinh_chi_nhanh', function (Blueprint $table) {
            $table->id();
            $table->string('ten_chi_nhanh');
            $table->string('dia_chi');
            $table->string('so_dien_thoai', 20);
            $table->string('truong_chi_nhanh');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cau_hinh_chi_nhanh');
    }
};
