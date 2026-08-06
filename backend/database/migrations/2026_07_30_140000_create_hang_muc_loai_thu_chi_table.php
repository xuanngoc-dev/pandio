<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hang_muc_loai_thu_chi', function (Blueprint $table) {
            $table->id();
            $table->string('ten_hang_muc');
            $table->text('ghi_chu')->nullable();
            $table->enum('trang_thai', ['hoat_dong', 'ngung_hoat_dong'])->default('hoat_dong');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hang_muc_loai_thu_chi');
    }
};
