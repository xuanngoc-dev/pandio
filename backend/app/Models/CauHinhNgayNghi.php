<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'ten_ngay_nghi',
    'ngay_bat_dau',
    'ngay_ket_thuc',
    'trang_thai',
])]
class CauHinhNgayNghi extends Model
{
    protected $table = 'cau_hinh_ngay_nghi';
}
