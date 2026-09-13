<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * FK đã có trong migration create bảng nhan_vien.
     * Chỉ thêm nếu constraint chưa tồn tại (tránh Duplicate foreign key trên production).
     */
    public function up(): void
    {
        if (! Schema::hasTable('nhan_vien') || ! Schema::hasColumn('nhan_vien', 'phong_ban_id')) {
            return;
        }

        if ($this->foreignKeyExists()) {
            return;
        }

        Schema::table('nhan_vien', function (Blueprint $table) {
            $table->foreign('phong_ban_id')
                ->references('id')
                ->on('phong_ban')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        // FK thuộc migration create — không drop ở đây.
    }

    private function foreignKeyExists(): bool
    {
        return DB::selectOne(
            'SELECT 1 AS ok FROM information_schema.table_constraints
             WHERE table_schema = DATABASE()
               AND table_name = ?
               AND constraint_name = ?
               AND constraint_type = ?
             LIMIT 1',
            ['nhan_vien', 'nhan_vien_phong_ban_id_foreign', 'FOREIGN KEY']
        ) !== null;
    }
};
