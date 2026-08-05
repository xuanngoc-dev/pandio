<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('nhan_vien', function (Blueprint $table) {
            $table->dropForeign(['phong_ban_id']);
        });

        Schema::table('nhan_vien', function (Blueprint $table) {
            $table->json('phong_ban_ids')->nullable()->after('user_id');
        });

        DB::table('nhan_vien')
            ->whereNotNull('phong_ban_id')
            ->orderBy('id')
            ->select(['id', 'phong_ban_id'])
            ->chunkById(100, function ($rows) {
                foreach ($rows as $row) {
                    DB::table('nhan_vien')
                        ->where('id', $row->id)
                        ->update([
                            'phong_ban_ids' => json_encode([(int) $row->phong_ban_id]),
                        ]);
                }
            });

        Schema::table('nhan_vien', function (Blueprint $table) {
            $table->dropColumn('phong_ban_id');
        });
    }

    public function down(): void
    {
        Schema::table('nhan_vien', function (Blueprint $table) {
            $table->unsignedBigInteger('phong_ban_id')->nullable()->after('user_id');
        });

        DB::table('nhan_vien')
            ->whereNotNull('phong_ban_ids')
            ->orderBy('id')
            ->select(['id', 'phong_ban_ids'])
            ->chunkById(100, function ($rows) {
                foreach ($rows as $row) {
                    $ids = is_string($row->phong_ban_ids)
                        ? json_decode($row->phong_ban_ids, true)
                        : $row->phong_ban_ids;

                    $firstId = is_array($ids) && $ids !== [] ? (int) $ids[0] : null;

                    DB::table('nhan_vien')
                        ->where('id', $row->id)
                        ->update(['phong_ban_id' => $firstId]);
                }
            });

        Schema::table('nhan_vien', function (Blueprint $table) {
            $table->dropColumn('phong_ban_ids');
            $table->foreign('phong_ban_id')
                ->references('id')
                ->on('phong_ban')
                ->nullOnDelete();
        });
    }
};
