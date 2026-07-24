<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'loai_nghi_phep',
    'buoi_nghi',
    'ngay_bat_dau',
    'ngay_ket_thuc',
    'ly_do',
    'trang_thai',
    'nguoi_duyet_id',
])]
class XinNghiPhep extends Model
{
    protected $table = 'xin_nghi_phep';

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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function nguoiDuyet(): BelongsTo
    {
        return $this->belongsTo(User::class, 'nguoi_duyet_id');
    }
}
