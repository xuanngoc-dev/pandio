<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('dat_mua_trang_phuc')) {
            return;
        }

        if (! Schema::hasColumn('dat_mua_trang_phuc', 'trang_thai')) {
            Schema::table('dat_mua_trang_phuc', function (Blueprint $table) {
                $table->enum('trang_thai', ['cho_duyet', 'da_duyet', 'huy_duyet'])
                    ->default('cho_duyet')
                    ->after('du_no');
                $table->index('trang_thai');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('dat_mua_trang_phuc')) {
            return;
        }

        if (Schema::hasColumn('dat_mua_trang_phuc', 'trang_thai')) {
            Schema::table('dat_mua_trang_phuc', function (Blueprint $table) {
                $table->dropIndex(['trang_thai']);
                $table->dropColumn('trang_thai');
            });
        }
    }
};
