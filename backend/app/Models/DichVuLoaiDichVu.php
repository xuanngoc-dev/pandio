<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'ten_dich_vu',
    'mo_ta',
    'trang_thai',
])]
class DichVuLoaiDichVu extends Model
{
    protected $table = 'dich_vu_loai_dich_vu';

    public function danhSachDichVu(): HasMany
    {
        return $this->hasMany(DichVuDanhSachDichVuLe::class, 'loai_dich_vu_id');
    }
}
