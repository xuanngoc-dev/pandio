<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dich_vu_loai_dich_vu', function (Blueprint $table) {
            $table->id();
            $table->string('ten_dich_vu');
            $table->text('mo_ta')->nullable();
            $table->enum('trang_thai', ['dang_hoat_dong', 'ngung_hoat_dong'])->default('dang_hoat_dong');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dich_vu_loai_dich_vu');
    }
};
