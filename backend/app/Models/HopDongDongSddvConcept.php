<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'ma_hop_dong_id',
    'concept_id',
])]
class HopDongDongSddvConcept extends Model
{
    protected $table = 'hop_dong_dong_sddv_concept';

    public function hopDong(): BelongsTo
    {
        return $this->belongsTo(HopDongSuDungDichVu::class, 'ma_hop_dong_id');
    }

    public function concept(): BelongsTo
    {
        return $this->belongsTo(Concept::class, 'concept_id');
    }
}
