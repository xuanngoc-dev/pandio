<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('khach_hang_note_khach_moi', function (Blueprint $table) {
            $table->id();
            $table->string('ten_khach');
            $table->string('sdt', 20)->nullable();
            $table->date('ngay_hen_lich')->nullable();
            $table->json('phu_trach_sale')->nullable(); // mảng id users (nhân viên)
            $table->text('ghi_chu')->nullable();
            $table->string('nguon_khach', 100)->nullable();
            $table->date('ngay_den_thuc_te')->nullable();
            $table->string('trang_thai', 50)->default('cho_hen');
            $table->string('tra_cuu_hd')->nullable();
            $table->string('hinh_thuc_dat_coc', 50)->nullable();
            $table->foreignId('nguoi_tao')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('ngay_hen_lich');
            $table->index('trang_thai');
            $table->index('sdt');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('khach_hang_note_khach_moi');
    }
};
