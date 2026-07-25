<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'hop_dong_id',
    'san_pham_id',
    'ghi_chu',
])]
class HopDongChoThueTrangPhucSanPhamChoThue extends Model
{
    protected $table = 'hop_dong_cho_thue_trang_phuc_san_pham_cho_thue';

    public function hopDong(): BelongsTo
    {
        return $this->belongsTo(HopDongChoThueTrangPhuc::class, 'hop_dong_id');
    }

    public function sanPham(): BelongsTo
    {
        return $this->belongsTo(TrangPhuc::class, 'san_pham_id');
    }
}
