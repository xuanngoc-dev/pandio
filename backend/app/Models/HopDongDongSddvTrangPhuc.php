<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'ma_hop_dong_id',
    'trang_phuc_id',
    'ngay_su_dung',
    'ngay_bat_dau',
    'ngay_ket_thuc',
])]
class HopDongDongSddvTrangPhuc extends Model
{
    protected $table = 'hop_dong_dong_sddv_trang_phuc';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'ngay_su_dung' => 'date:Y-m-d',
            'ngay_bat_dau' => 'date:Y-m-d',
            'ngay_ket_thuc' => 'date:Y-m-d',
        ];
    }

    public function hopDong(): BelongsTo
    {
        return $this->belongsTo(HopDongSuDungDichVu::class, 'ma_hop_dong_id');
    }

    public function trangPhuc(): BelongsTo
    {
        return $this->belongsTo(TrangPhuc::class, 'trang_phuc_id');
    }
}
