<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'ma_hop_dong',
    'ten_khach_hang',
    'sdt_khach_hang',
    'ngay_thue',
    'ngay_tra_du_kien',
    'ngay_tra_chinh_thuc',
    'so_ngay_thue',
    'tong_tien',
    'giam_gia',
    'tien_coc',
    'trang_thai',
    'nguoi_cho_thue',
    'nguoi_tham_gia',
    'ghi_chu_sale',
    'ghi_chu_khach',
])]
class HopDongChoThueTrangPhuc extends Model
{
    protected $table = 'hop_dong_cho_thue_trang_phuc';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'ngay_thue' => 'date',
            'ngay_tra_du_kien' => 'date',
            'ngay_tra_chinh_thuc' => 'date',
            'so_ngay_thue' => 'integer',
            'tong_tien' => 'integer',
            'giam_gia' => 'integer',
            'tien_coc' => 'integer',
            'nguoi_tham_gia' => 'array',
        ];
    }

    public function nguoiChoThueUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'nguoi_cho_thue');
    }

    public function sanPhamChoThue(): HasMany
    {
        return $this->hasMany(HopDongChoThueTrangPhucSanPhamChoThue::class, 'hop_dong_id');
    }
}
