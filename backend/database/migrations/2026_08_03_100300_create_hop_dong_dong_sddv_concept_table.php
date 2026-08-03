<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hop_dong_dong_sddv_concept', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ma_hop_dong_id');
            $table->unsignedBigInteger('concept_id');
            $table->timestamps();

            $table->foreign('ma_hop_dong_id', 'hdd_sddv_concept_hop_dong_fk')
                ->references('id')
                ->on('hop_dong_su_dung_dich_vu')
                ->cascadeOnDelete();
            $table->foreign('concept_id', 'hdd_sddv_concept_concept_fk')
                ->references('id')
                ->on('concept')
                ->restrictOnDelete();

            $table->unique(['ma_hop_dong_id', 'concept_id'], 'hdd_sddv_concept_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hop_dong_dong_sddv_concept');
    }
};
