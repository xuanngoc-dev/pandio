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
        Schema::create('cau_hinh_form_danh_gia_mau', function (Blueprint $table) {
            $table->id();
            $table->string('ten_form');
            $table->string('slug')->unique();
            $table->json('cau_hoi')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cau_hinh_form_danh_gia_mau');
    }
};
