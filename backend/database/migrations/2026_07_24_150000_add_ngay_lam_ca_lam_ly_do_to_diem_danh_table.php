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
        Schema::table('diem_danh', function (Blueprint $table) {
            $table->date('ngay_lam')->nullable()->after('user_id');
            $table->foreignId('ca_lam_id')
                ->nullable()
                ->after('ngay_lam')
                ->constrained('cau_hinh_ca_lam_viec')
                ->nullOnDelete();
            $table->text('ly_do')->nullable()->after('tien_phat_ve_som');
            $table->unique(['user_id', 'ngay_lam']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('diem_danh', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'ngay_lam']);
            $table->dropConstrainedForeignId('ca_lam_id');
            $table->dropColumn(['ngay_lam', 'ly_do']);
        });
    }
};
