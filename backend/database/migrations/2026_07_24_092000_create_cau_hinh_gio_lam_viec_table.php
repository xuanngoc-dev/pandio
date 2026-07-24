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
        Schema::create('cau_hinh_gio_lam_viec', function (Blueprint $table) {
            $table->id();
            $table->string('ten_cau_hinh');
            $table->time('gio_vao_buoi_sang');
            $table->time('gio_tan_buoi_sang');
            $table->time('gio_vao_buoi_chieu');
            $table->time('gio_tan_buoi_chieu');
            $table->string('su_dung')->default('khong'); // co | khong — đúng 1 bản ghi = co
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cau_hinh_gio_lam_viec');
    }
};
