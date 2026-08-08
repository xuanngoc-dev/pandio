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
        Schema::table('cong_viec_ca_nhan', function (Blueprint $table) {
            $table->string('lien_ket', 500)->nullable()->after('ghi_chu');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cong_viec_ca_nhan', function (Blueprint $table) {
            $table->dropColumn('lien_ket');
        });
    }
};
