<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'hop_dong_danh_gia_id',
    'form_danh_gia_id',
    'noi_dung_danh_gia',
])]
class HopDongSuDungDichVuFormDanhGia extends Model
{
    protected $table = 'hop_dong_su_dung_dich_vu_form_danh_gia';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'noi_dung_danh_gia' => 'array',
        ];
    }

    public function hopDong(): BelongsTo
    {
        return $this->belongsTo(HopDongSuDungDichVu::class, 'hop_dong_danh_gia_id');
    }

    public function formDanhGia(): BelongsTo
    {
        return $this->belongsTo(CauHinhFormDanhGiaMau::class, 'form_danh_gia_id');
    }
}
