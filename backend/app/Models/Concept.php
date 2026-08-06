<?php

namespace App\Models;

use App\Models\Concerns\HasPublicMediaUrls;
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
    use HasPublicMediaUrls;

    protected $table = 'concept';

    public function danhMuc(): BelongsTo
    {
        return $this->belongsTo(DanhMucConcept::class, 'loai_concept');
    }

    /**
     * @return list<string>
     */
    protected function mediaUrlAttributes(): array
    {
        return ['hinh_anh'];
    }
}
