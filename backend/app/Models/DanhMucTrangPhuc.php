<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'ten_danh_muc',
    'ma_danh_muc',
    'mo_ta',
])]
class DanhMucTrangPhuc extends Model
{
    protected $table = 'danh_muc_trang_phuc';
}
