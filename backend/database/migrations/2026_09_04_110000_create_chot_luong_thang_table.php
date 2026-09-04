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
        Schema::create('chot_luong_thang', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('thang'); // 1–12
            $table->unsignedSmallInteger('nam');
            $table->foreignId('nguoi_chot_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->json('du_lieu_chot')->nullable();
            $table->string('trang_thai', 32)->default('chua_chot'); // chua_chot | da_chot
            $table->timestamps();

            $table->unique(['thang', 'nam']);
            $table->index('trang_thai');
            $table->index('nguoi_chot_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chot_luong_thang');
    }
};
