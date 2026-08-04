<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'ngan_hang',
    'so_tai_khoan',
    'chu_tai_khoan',
    'chi_nhanh',
    'hinh_anh_logo',
    'mac_dinh',
    'trang_thai',
])]
class CauHinhTaiKhoanThanhToan extends Model
{
    protected $table = 'cau_hinh_tai_khoan_thanh_toan';
}
