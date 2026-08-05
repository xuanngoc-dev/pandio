<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'ma_loai_thong_bao',
    'ten_loai_thong_bao',
    'icon',
    'trang_thai',
])]
class DanhMucLoaiThongBao extends Model
{
    protected $table = 'danh_muc_loai_thong_bao';
}
