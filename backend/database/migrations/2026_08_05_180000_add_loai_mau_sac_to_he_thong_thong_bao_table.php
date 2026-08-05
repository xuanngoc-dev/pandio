<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('he_thong_thong_bao', function (Blueprint $table) {
            $table->string('loai_mau_sac', 32)
                ->default('blue')
                ->after('loai_thong_bao_id');
        });
    }

    public function down(): void
    {
        Schema::table('he_thong_thong_bao', function (Blueprint $table) {
            $table->dropColumn('loai_mau_sac');
        });
    }
};
