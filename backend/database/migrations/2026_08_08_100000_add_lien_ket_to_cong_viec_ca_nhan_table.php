<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('cong_viec_ca_nhan')) {
            return;
        }

        if (Schema::hasColumn('cong_viec_ca_nhan', 'lien_ket')) {
            return;
        }

        Schema::table('cong_viec_ca_nhan', function (Blueprint $table) {
            $column = $table->string('lien_ket', 500)->nullable();
            if (Schema::hasColumn('cong_viec_ca_nhan', 'ghi_chu')) {
                $column->after('ghi_chu');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('cong_viec_ca_nhan') || ! Schema::hasColumn('cong_viec_ca_nhan', 'lien_ket')) {
            return;
        }

        Schema::table('cong_viec_ca_nhan', function (Blueprint $table) {
            $table->dropColumn('lien_ket');
        });
    }
};
