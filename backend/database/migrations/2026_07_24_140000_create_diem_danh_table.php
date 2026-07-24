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
        Schema::create('diem_danh', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->dateTime('gio_vao')->nullable();
            $table->dateTime('gio_ra')->nullable();
            $table->string('di_muon')->default('khong'); // co | khong
            $table->string('ve_som')->default('khong'); // co | khong
            $table->unsignedInteger('thoi_gian_di_muon')->default(0); // phút
            $table->unsignedInteger('thoi_gian_ve_som')->default(0); // phút
            $table->decimal('tien_phat_di_muon', 15, 2)->default(0);
            $table->decimal('tien_phat_ve_som', 15, 2)->default(0);
            $table->string('ip_checkin')->nullable();
            $table->string('ip_checkout')->nullable();
            $table->decimal('gio_lam_co_ban', 8, 2)->default(0);
            $table->decimal('gio_lam_tang_ca', 8, 2)->default(0);
            $table->decimal('luong_co_ban', 15, 2)->default(0);
            $table->decimal('luong_tang_ca', 15, 2)->default(0);
            $table->text('ghi_chu')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('diem_danh');
    }
};
