<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('concept', function (Blueprint $table) {
            $table->string('hinh_anh', 1000)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('concept', function (Blueprint $table) {
            $table->string('hinh_anh', 255)->nullable()->change();
        });
    }
};
