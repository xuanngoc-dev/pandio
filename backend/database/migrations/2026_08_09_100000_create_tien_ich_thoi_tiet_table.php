<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tien_ich_thoi_tiet', function (Blueprint $table) {
            $table->id();
            $table->date('ngay')->unique();
            $table->string('dia_diem')->nullable();
            $table->string('mo_ta')->nullable();
            $table->unsignedTinyInteger('ty_le_mua')->default(0);
            $table->decimal('toc_do_gio', 6, 2)->nullable()->comment('Tốc độ gió (m/s)');
            $table->smallInteger('nhiet_do_min')->nullable()->comment('Nhiệt độ thấp nhất (°C)');
            $table->smallInteger('nhiet_do_max')->nullable()->comment('Nhiệt độ cao nhất (°C)');
            $table->string('icon')->nullable()->comment('Nhãn thời tiết: mặt trời, mây cụm, mây rải rác, mưa nhẹ, trời quang...');
            $table->string('icon_code', 16)->nullable()->comment('Mã icon OpenWeatherMap, vd: 02d');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tien_ich_thoi_tiet');
    }
};
