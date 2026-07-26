<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'ma_nhom',
    'ten_nhom',
    'gia_goc',
    'gia_khuyen_mai',
    'loai_dich_vu_id',
    'so_diem_chup',
    'so_anh_chinh_sua',
    'dich_vu_le_ids',
    'trang_thai',
    'ghi_chu',
])]
class DichVuDanhSachDichNhomDichVu extends Model
{
    protected $table = 'dich_vu_danh_sach_dich_nhom_dich_vu';

    protected function casts(): array
    {
        return [
            'dich_vu_le_ids' => 'array',
            'gia_goc' => 'decimal:0',
            'gia_khuyen_mai' => 'decimal:0',
            'so_diem_chup' => 'integer',
            'so_anh_chinh_sua' => 'integer',
        ];
    }

    public function loaiDichVu(): BelongsTo
    {
        return $this->belongsTo(DichVuLoaiDichVu::class, 'loai_dich_vu_id');
    }
}
