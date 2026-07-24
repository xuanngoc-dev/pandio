<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'ten_danh_muc',
    'mo_ta',
])]
class DanhMucConcept extends Model
{
    protected $table = 'danh_muc_concept';

    public function concepts(): HasMany
    {
        return $this->hasMany(Concept::class, 'loai_concept');
    }
}
