<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('report_quang_cao', function (Blueprint $table) {
            $table->id();
            $table->date('ngay');

            // Chi phí quảng cáo
            $table->decimal('cpqc_tiktok', 15, 0)->nullable()->default(0);
            $table->decimal('cpqc_fb', 15, 0)->nullable()->default(0);
            $table->decimal('cpqc_google', 15, 0)->nullable()->default(0);

            // Inbox & CPI
            $table->unsignedInteger('inbox_tiktok')->nullable()->default(0);
            $table->decimal('cpi_tiktok', 15, 0)->nullable()->default(0);
            $table->unsignedInteger('inbox_fb')->nullable()->default(0);
            $table->decimal('cpi_fb', 15, 0)->nullable()->default(0);

            // Khách hàng
            $table->unsignedInteger('kh_tiktok')->nullable()->default(0);
            $table->unsignedInteger('kh_fb')->nullable()->default(0);
            $table->unsignedInteger('kh_google')->nullable()->default(0);

            // CPL
            $table->decimal('tcpl_tiktok', 15, 0)->nullable()->default(0);
            $table->decimal('cpl_fb', 15, 0)->nullable()->default(0);
            $table->decimal('cpl_google', 15, 0)->nullable()->default(0);

            // Lịch hẹn
            $table->unsignedInteger('lich_hen')->nullable()->default(0);
            $table->unsignedInteger('khach_den_tu_hen')->nullable()->default(0);

            $table->text('ghi_chu')->nullable();
            $table->timestamps();

            $table->index('ngay');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_quang_cao');
    }
};
