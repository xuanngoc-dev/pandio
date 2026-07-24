<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('nha_cung_cap_trang_phuc', function (Blueprint $table) {
            $table->text('ghi_chu')->nullable()->after('email');
        });
    }

    public function down(): void
    {
        Schema::table('nha_cung_cap_trang_phuc', function (Blueprint $table) {
            $table->dropColumn('ghi_chu');
        });
    }
};
