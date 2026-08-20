<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'ten_dich_vu',
    'ghi_chu',
    'trang_thai',
])]
class DanhMucLoaiQuayChup extends Model
{
    protected $table = 'danh_muc_loai_quay_chup';
}
