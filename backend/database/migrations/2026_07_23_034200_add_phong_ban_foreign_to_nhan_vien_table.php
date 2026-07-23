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
        Schema::table('nhan_vien', function (Blueprint $table) {
            $table->foreign('phong_ban_id')
                ->references('id')
                ->on('phong_ban')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('nhan_vien', function (Blueprint $table) {
            $table->dropForeign(['phong_ban_id']);
        });
    }
};
