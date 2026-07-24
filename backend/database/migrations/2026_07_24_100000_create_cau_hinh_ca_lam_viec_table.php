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
        Schema::create('cau_hinh_ca_lam_viec', function (Blueprint $table) {
            $table->id();
            $table->string('ten_ca');
            $table->time('gio_bat_dau');
            $table->time('gio_ket_thuc');
            $table->string('trang_thai')->default('co'); // co | khong
            $table->text('ghi_chu')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cau_hinh_ca_lam_viec');
    }
};
