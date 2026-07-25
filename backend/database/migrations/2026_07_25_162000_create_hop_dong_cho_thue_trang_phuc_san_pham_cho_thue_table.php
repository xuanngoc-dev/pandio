<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hop_dong_cho_thue_trang_phuc_san_pham_cho_thue', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('hop_dong_id');
            $table->unsignedBigInteger('san_pham_id');
            $table->text('ghi_chu')->nullable();
            $table->timestamps();

            $table->foreign('hop_dong_id', 'hdcttp_hop_dong_fk')
                ->references('id')
                ->on('hop_dong_cho_thue_trang_phuc')
                ->cascadeOnDelete();
            $table->foreign('san_pham_id', 'hdcttp_san_pham_fk')
                ->references('id')
                ->on('trang_phuc')
                ->restrictOnDelete();

            $table->unique(['hop_dong_id', 'san_pham_id'], 'hdcttp_hop_dong_san_pham_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hop_dong_cho_thue_trang_phuc_san_pham_cho_thue');
    }
};
