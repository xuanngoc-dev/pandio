<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'ten_chi_nhanh',
    'dia_chi',
    'so_dien_thoai',
    'email',
    'truong_chi_nhanh',
])]
class CauHinhChiNhanh extends Model
{
    protected $table = 'cau_hinh_chi_nhanh';
}
