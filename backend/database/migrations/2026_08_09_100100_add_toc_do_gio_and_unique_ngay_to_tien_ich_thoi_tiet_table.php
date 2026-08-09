<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tien_ich_thoi_tiet', function (Blueprint $table) {
            if (! Schema::hasColumn('tien_ich_thoi_tiet', 'toc_do_gio')) {
                $table->decimal('toc_do_gio', 6, 2)
                    ->nullable()
                    ->after('ty_le_mua')
                    ->comment('Tốc độ gió (m/s)');
            }
        });

        $this->dropIndexIfExists('tien_ich_thoi_tiet', 'tien_ich_thoi_tiet_ngay_dia_diem_unique');
        $this->addUniqueIfMissing('tien_ich_thoi_tiet', 'ngay', 'tien_ich_thoi_tiet_ngay_unique');
    }

    public function down(): void
    {
        $this->dropIndexIfExists('tien_ich_thoi_tiet', 'tien_ich_thoi_tiet_ngay_unique');

        Schema::table('tien_ich_thoi_tiet', function (Blueprint $table) {
            $table->unique(['ngay', 'dia_diem']);

            if (Schema::hasColumn('tien_ich_thoi_tiet', 'toc_do_gio')) {
                $table->dropColumn('toc_do_gio');
            }
        });
    }

    private function dropIndexIfExists(string $table, string $index): void
    {
        $exists = collect(DB::select("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$index]))
            ->isNotEmpty();

        if ($exists) {
            Schema::table($table, function (Blueprint $blueprint) use ($index) {
                $blueprint->dropUnique($index);
            });
        }
    }

    private function addUniqueIfMissing(string $table, string $column, string $index): void
    {
        $exists = collect(DB::select("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$index]))
            ->isNotEmpty();

        if (! $exists) {
            Schema::table($table, function (Blueprint $blueprint) use ($column, $index) {
                $blueprint->unique($column, $index);
            });
        }
    }
};
