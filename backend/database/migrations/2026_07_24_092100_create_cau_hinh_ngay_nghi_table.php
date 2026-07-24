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
        Schema::create('cau_hinh_ngay_nghi', function (Blueprint $table) {
            $table->id();
            $table->string('ten_ngay_nghi');
            $table->date('ngay_bat_dau');
            $table->date('ngay_ket_thuc');
            $table->string('trang_thai')->default('active'); // active | inactive
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cau_hinh_ngay_nghi');
    }
};
