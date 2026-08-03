<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hop_dong_su_dung_dich_vu', function (Blueprint $table) {
            $table->dropForeign(['loai_hop_dong_id']);
        });

        Schema::table('hop_dong_su_dung_dich_vu', function (Blueprint $table) {
            $table->unsignedBigInteger('loai_hop_dong_id')->nullable()->change();
            $table->text('ghi_chu_sale')->nullable()->after('yeu_cau_dac_biet');
        });

        Schema::table('hop_dong_su_dung_dich_vu', function (Blueprint $table) {
            $table->foreign('loai_hop_dong_id')
                ->references('id')
                ->on('loai_hop_dong')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('hop_dong_su_dung_dich_vu', function (Blueprint $table) {
            $table->dropForeign(['loai_hop_dong_id']);
            $table->dropColumn('ghi_chu_sale');
        });

        Schema::table('hop_dong_su_dung_dich_vu', function (Blueprint $table) {
            $table->unsignedBigInteger('loai_hop_dong_id')->nullable(false)->change();
        });

        Schema::table('hop_dong_su_dung_dich_vu', function (Blueprint $table) {
            $table->foreign('loai_hop_dong_id')
                ->references('id')
                ->on('loai_hop_dong')
                ->restrictOnDelete();
        });
    }
};
