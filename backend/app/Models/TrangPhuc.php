<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'hinh_anh',
    'ma_san_pham',
    'ten_san_pham',
    'danh_muc',
    'nha_cung_cap',
    'chi_nhanh',
    'gia_tri',
    'gia_cho_thue',
    'phan_loai_chi_phi',
    'tinh_trang',
    'ghi_chu',
    'trang_thai',
    'thong_tin_them',
])]
class TrangPhuc extends Model
{
    protected $table = 'trang_phuc';

    protected function casts(): array
    {
        return [
            'gia_tri' => 'integer',
            'gia_cho_thue' => 'integer',
            'trang_thai' => 'integer',
            'thong_tin_them' => 'array',
        ];
    }

    public function danhMucTrangPhuc(): BelongsTo
    {
        return $this->belongsTo(DanhMucTrangPhuc::class, 'danh_muc');
    }

    public function nhaCungCapTrangPhuc(): BelongsTo
    {
        return $this->belongsTo(NhaCungCapTrangPhuc::class, 'nha_cung_cap');
    }

    public function cauHinhChiNhanh(): BelongsTo
    {
        return $this->belongsTo(CauHinhChiNhanh::class, 'chi_nhanh');
    }

    public function lichChoThue(): HasMany
    {
        return $this->hasMany(HopDongChoThueTrangPhucSanPhamChoThue::class, 'san_pham_id');
    }
}
