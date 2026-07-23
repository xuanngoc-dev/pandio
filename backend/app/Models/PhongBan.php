<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'ten_phong_ban',
    'ma_phong_ban',
    'truong_phong',
    'mo_ta',
    'ghi_chu',
])]
class PhongBan extends Model
{
    protected $table = 'phong_ban';
}
