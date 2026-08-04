<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'hop_dong_id',
    'san_pham_id',
    'ngay_bat_dau',
    'ngay_ket_thuc',
    'ghi_chu',
])]
class HopDongChoThueTrangPhucSanPhamChoThue extends Model
{
    protected $table = 'hop_dong_cho_thue_trang_phuc_san_pham_cho_thue';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'ngay_bat_dau' => 'date',
            'ngay_ket_thuc' => 'date',
        ];
    }

    public function hopDong(): BelongsTo
    {
        return $this->belongsTo(HopDongChoThueTrangPhuc::class, 'hop_dong_id');
    }

    public function sanPham(): BelongsTo
    {
        return $this->belongsTo(TrangPhuc::class, 'san_pham_id');
    }
}
