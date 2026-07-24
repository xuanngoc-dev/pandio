<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'ten_ca',
    'gio_bat_dau',
    'gio_ket_thuc',
    'trang_thai',
    'ghi_chu',
])]
class CauHinhCaLamViec extends Model
{
    protected $table = 'cau_hinh_ca_lam_viec';

    public function dangKyCa(): HasMany
    {
        return $this->hasMany(DangKyCaLamViec::class, 'ca_lam_id');
    }
}
