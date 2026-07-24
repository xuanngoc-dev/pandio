<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'ma_nha_cung_cap',
    'ten_nha_cung_cap',
    'dia_chi',
    'so_dien_thoai',
    'email',
    'ghi_chu',
])]
class NhaCungCapTrangPhuc extends Model
{
    protected $table = 'nha_cung_cap_trang_phuc';
}
