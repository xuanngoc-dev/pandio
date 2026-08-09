<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tien_ich_thoi_tiet', function (Blueprint $table) {
            if (! Schema::hasColumn('tien_ich_thoi_tiet', 'nhiet_do_min')) {
                $table->smallInteger('nhiet_do_min')
                    ->nullable()
                    ->after('toc_do_gio')
                    ->comment('Nhiệt độ thấp nhất (°C)');
            }

            if (! Schema::hasColumn('tien_ich_thoi_tiet', 'nhiet_do_max')) {
                $table->smallInteger('nhiet_do_max')
                    ->nullable()
                    ->after('nhiet_do_min')
                    ->comment('Nhiệt độ cao nhất (°C)');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tien_ich_thoi_tiet', function (Blueprint $table) {
            $columns = [];
            if (Schema::hasColumn('tien_ich_thoi_tiet', 'nhiet_do_min')) {
                $columns[] = 'nhiet_do_min';
            }
            if (Schema::hasColumn('tien_ich_thoi_tiet', 'nhiet_do_max')) {
                $columns[] = 'nhiet_do_max';
            }
            if ($columns) {
                $table->dropColumn($columns);
            }
        });
    }
};
