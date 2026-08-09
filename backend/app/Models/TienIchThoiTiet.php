<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'ngay',
    'dia_diem',
    'mo_ta',
    'ty_le_mua',
    'toc_do_gio',
    'icon',
    'icon_code',
])]
class TienIchThoiTiet extends Model
{
    protected $table = 'tien_ich_thoi_tiet';

    protected function casts(): array
    {
        return [
            'ngay' => 'date',
            'ty_le_mua' => 'integer',
            'toc_do_gio' => 'float',
        ];
    }
}
