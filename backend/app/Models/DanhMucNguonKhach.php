<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'ten_nguon_khach',
    'ghi_chu',
    'trang_thai',
])]
class DanhMucNguonKhach extends Model
{
    protected $table = 'danh_muc_nguon_khach';
}
