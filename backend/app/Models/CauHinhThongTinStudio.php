<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'ten_studio',
    'khau_hieu',
    'logo',
    'dia_chi',
    'email',
    'so_dien_thoai',
    'ma_so_thue',
    'mac_dinh',
])]
class CauHinhThongTinStudio extends Model
{
    protected $table = 'cau_hinh_thong_tin_studio';
}
