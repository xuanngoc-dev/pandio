<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('nhan_vien', function (Blueprint $table) {
            $table->foreignId('vai_tro_id')
                ->nullable()
                ->after('phong_ban_ids')
                ->constrained('vai_tro')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('nhan_vien', function (Blueprint $table) {
            $table->dropConstrainedForeignId('vai_tro_id');
        });
    }
};
