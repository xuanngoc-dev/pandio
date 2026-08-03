<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'ma_hop_dong_id',
    'combo_id',
    'so_luong',
    'thanh_tien',
    'ghi_chu',
])]
class HopDongDongSddvCombo extends Model
{
    protected $table = 'hop_dong_dong_sddv_combos';

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

    public function combo(): BelongsTo
    {
        return $this->belongsTo(DichVuDanhSachDichNhomDichVu::class, 'combo_id');
    }
}
