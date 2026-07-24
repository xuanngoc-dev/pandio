<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('concept', function (Blueprint $table) {
            $table->id();
            $table->string('hinh_anh')->nullable();
            $table->foreignId('loai_concept')
                ->constrained('danh_muc_concept')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->string('ma_concept')->unique();
            $table->string('ten_concept');
            $table->string('dia_diem')->nullable();
            $table->string('trang_thai')->default('dang_su_dung'); // dang_su_dung | ngung_su_dung
            $table->text('mo_ta')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('concept');
    }
};
