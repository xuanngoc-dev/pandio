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
        if (
            Schema::hasTable('cau_hinh_gio_lam_viec')
            && Schema::hasColumn('cau_hinh_gio_lam_viec', 'mac_dinh')
            && ! Schema::hasColumn('cau_hinh_gio_lam_viec', 'su_dung')
        ) {
            Schema::table('cau_hinh_gio_lam_viec', function (Blueprint $table) {
                $table->renameColumn('mac_dinh', 'su_dung');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (
            Schema::hasTable('cau_hinh_gio_lam_viec')
            && Schema::hasColumn('cau_hinh_gio_lam_viec', 'su_dung')
            && ! Schema::hasColumn('cau_hinh_gio_lam_viec', 'mac_dinh')
        ) {
            Schema::table('cau_hinh_gio_lam_viec', function (Blueprint $table) {
                $table->renameColumn('su_dung', 'mac_dinh');
            });
        }
    }
};
