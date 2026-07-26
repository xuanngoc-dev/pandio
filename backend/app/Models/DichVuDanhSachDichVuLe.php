<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'ma_dich_vu',
    'ten_dich_vu',
    'loai_dich_vu_id',
    'loai_dich_vu_ids',
    'gia_goc',
    'gia_khuyen_mai',
    'mo_ta',
    'trang_thai',
    'ghi_chu',
])]
class DichVuDanhSachDichVuLe extends Model
{
    protected $table = 'dich_vu_danh_sach_dich_vu_le';

    protected function casts(): array
    {
        return [
            'loai_dich_vu_ids' => 'array',
            'gia_goc' => 'decimal:0',
            'gia_khuyen_mai' => 'decimal:0',
        ];
    }

    public function loaiDichVu(): BelongsTo
    {
        return $this->belongsTo(DichVuLoaiDichVu::class, 'loai_dich_vu_id');
    }
}
