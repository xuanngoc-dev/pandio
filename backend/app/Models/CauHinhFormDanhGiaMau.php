<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'ten_form',
    'slug',
    'cau_hoi',
])]
class CauHinhFormDanhGiaMau extends Model
{
    protected $table = 'cau_hinh_form_danh_gia_mau';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'cau_hoi' => 'array',
        ];
    }
}
