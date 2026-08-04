<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hop_dong_cho_thue_trang_phuc', function (Blueprint $table) {
            $table->unsignedBigInteger('thanh_tien')->default(0)->after('giam_gia');
        });

        DB::table('hop_dong_cho_thue_trang_phuc')->update([
            'thanh_tien' => DB::raw('GREATEST(0, CAST(tong_tien AS SIGNED) - CAST(giam_gia AS SIGNED))'),
        ]);
    }

    public function down(): void
    {
        Schema::table('hop_dong_cho_thue_trang_phuc', function (Blueprint $table) {
            $table->dropColumn('thanh_tien');
        });
    }
};
