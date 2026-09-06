<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hop_dong_su_dung_dich_vu_form_danh_gia', function (Blueprint $table) {
            $table->unique(
                ['hop_dong_danh_gia_id', 'form_danh_gia_id'],
                'hdd_sddv_form_dg_hop_dong_form_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('hop_dong_su_dung_dich_vu_form_danh_gia', function (Blueprint $table) {
            $table->dropUnique('hdd_sddv_form_dg_hop_dong_form_unique');
        });
    }
};
