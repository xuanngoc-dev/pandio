<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'ten_ip',
    'dia_chi_ip',
    'ghi_chu',
    'trang_thai',
])]
class IpDiemDanh extends Model
{
    protected $table = 'ip_diem_danh';
}
