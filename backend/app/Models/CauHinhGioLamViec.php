<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'ten_cau_hinh',
    'gio_vao_buoi_sang',
    'gio_tan_buoi_sang',
    'gio_vao_buoi_chieu',
    'gio_tan_buoi_chieu',
    'su_dung',
])]
class CauHinhGioLamViec extends Model
{
    protected $table = 'cau_hinh_gio_lam_viec';
}
