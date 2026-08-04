<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hop_dong_cho_thue_trang_phuc', function (Blueprint $table) {
            $table->unsignedBigInteger('uu_dai_tat_toan')
                ->default(0)
                ->after('phi_phu_thu');
        });
    }

    public function down(): void
    {
        Schema::table('hop_dong_cho_thue_trang_phuc', function (Blueprint $table) {
            $table->dropColumn('uu_dai_tat_toan');
        });
    }
};
