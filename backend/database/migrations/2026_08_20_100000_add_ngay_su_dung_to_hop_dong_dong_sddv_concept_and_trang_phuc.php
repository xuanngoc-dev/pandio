<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hop_dong_dong_sddv_concept', function (Blueprint $table) {
            if (! Schema::hasColumn('hop_dong_dong_sddv_concept', 'ngay_su_dung')) {
                $table->date('ngay_su_dung')->nullable()->after('concept_id');
            }
        });

        Schema::table('hop_dong_dong_sddv_concept', function (Blueprint $table) {
            $table->index('ma_hop_dong_id', 'hdd_sddv_concept_hop_dong_idx');
        });

        Schema::table('hop_dong_dong_sddv_concept', function (Blueprint $table) {
            $table->dropUnique('hdd_sddv_concept_unique');
            $table->unique(
                ['ma_hop_dong_id', 'concept_id', 'ngay_su_dung'],
                'hdd_sddv_concept_ngay_unique',
            );
        });

        Schema::table('hop_dong_dong_sddv_trang_phuc', function (Blueprint $table) {
            if (! Schema::hasColumn('hop_dong_dong_sddv_trang_phuc', 'ngay_su_dung')) {
                $table->date('ngay_su_dung')->nullable()->after('trang_phuc_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('hop_dong_dong_sddv_concept', function (Blueprint $table) {
            $table->dropUnique('hdd_sddv_concept_ngay_unique');
        });

        Schema::table('hop_dong_dong_sddv_concept', function (Blueprint $table) {
            $table->dropColumn('ngay_su_dung');
            $table->unique(['ma_hop_dong_id', 'concept_id'], 'hdd_sddv_concept_unique');
            $table->dropIndex('hdd_sddv_concept_hop_dong_idx');
        });

        Schema::table('hop_dong_dong_sddv_trang_phuc', function (Blueprint $table) {
            $table->dropColumn('ngay_su_dung');
        });
    }
};
