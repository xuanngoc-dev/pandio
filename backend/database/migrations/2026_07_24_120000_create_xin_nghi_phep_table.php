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
        Schema::create('xin_nghi_phep', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('loai_nghi_phep'); // di_muon, ve_som, nghi_nua_ngay, nghi_1_ngay, nghi_nhieu_ngay
            $table->string('buoi_nghi')->nullable(); // sang, chieu, ca_ngay
            $table->date('ngay_bat_dau');
            $table->date('ngay_ket_thuc')->nullable();
            $table->text('ly_do')->nullable();
            $table->string('trang_thai')->default('cho_duyet'); // cho_duyet, da_duyet, tu_choi
            $table->foreignId('nguoi_duyet_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('xin_nghi_phep');
    }
};
