<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('nhan_vien')) {
            return;
        }

        if (Schema::hasColumn('nhan_vien', 'vai_tro_id')) {
            return;
        }

        Schema::table('nhan_vien', function (Blueprint $table) {
            $column = $table->foreignId('vai_tro_id')
                ->nullable()
                ->constrained('vai_tro')
                ->nullOnDelete();

            if (Schema::hasColumn('nhan_vien', 'phong_ban_ids')) {
                $column->after('phong_ban_ids');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('nhan_vien') || ! Schema::hasColumn('nhan_vien', 'vai_tro_id')) {
            return;
        }

        Schema::table('nhan_vien', function (Blueprint $table) {
            $table->dropConstrainedForeignId('vai_tro_id');
        });
    }
};
