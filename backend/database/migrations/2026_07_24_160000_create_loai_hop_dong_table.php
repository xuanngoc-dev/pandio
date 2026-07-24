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
        Schema::create('loai_hop_dong', function (Blueprint $table) {
            $table->id();
            $table->string('ten_hop_dong');
            $table->string('ma_hop_dong')->unique();
            $table->json('noi_dung')->nullable();
            $table->enum('trang_thai', ['hoat_dong', 'ngung_hoat_dong'])->default('hoat_dong');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loai_hop_dong');
    }
};
