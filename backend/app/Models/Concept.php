<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'hinh_anh',
    'loai_concept',
    'ma_concept',
    'ten_concept',
    'dia_diem',
    'trang_thai',
    'mo_ta',
])]
class Concept extends Model
{
    protected $table = 'concept';

    public function danhMuc(): BelongsTo
    {
        return $this->belongsTo(DanhMucConcept::class, 'loai_concept');
    }
}
