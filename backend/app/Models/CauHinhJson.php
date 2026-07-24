<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'thong_tin_cau_hinh',
])]
class CauHinhJson extends Model
{
    protected $table = 'cau_hinh_json';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'thong_tin_cau_hinh' => 'array',
        ];
    }
}
