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
        Schema::table('cau_hinh_chi_nhanh', function (Blueprint $table) {
            $table->string('email')->nullable()->after('so_dien_thoai');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cau_hinh_chi_nhanh', function (Blueprint $table) {
            $table->dropColumn('email');
        });
    }
};
