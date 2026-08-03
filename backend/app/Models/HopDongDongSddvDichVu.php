<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'ma_hop_dong_id',
    'dich_vu_id',
    'so_luong',
    'thanh_tien',
    'ghi_chu',
])]
class HopDongDongSddvDichVu extends Model
{
    protected $table = 'hop_dong_dong_sddv_dich_vu';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'so_luong' => 'integer',
            'thanh_tien' => 'integer',
        ];
    }

    public function hopDong(): BelongsTo
    {
        return $this->belongsTo(HopDongSuDungDichVu::class, 'ma_hop_dong_id');
    }

    public function dichVu(): BelongsTo
    {
        return $this->belongsTo(DichVuDanhSachDichVuLe::class, 'dich_vu_id');
    }
}
