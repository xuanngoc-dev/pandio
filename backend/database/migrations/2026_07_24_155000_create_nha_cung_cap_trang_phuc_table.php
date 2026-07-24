<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nha_cung_cap_trang_phuc', function (Blueprint $table) {
            $table->id();
            $table->string('ma_nha_cung_cap')->unique();
            $table->string('ten_nha_cung_cap');
            $table->string('dia_chi')->nullable();
            $table->string('so_dien_thoai')->nullable();
            $table->string('email')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nha_cung_cap_trang_phuc');
    }
};
