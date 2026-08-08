<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'nguoi_giao_viec_id',
    'nguoi_phu_trach_viec_ids',
    'tieu_de',
    'mo_ta',
    'ghi_chu',
    'lien_ket',
    'thoi_gian_thuc_hien',
    'muc_do_uu_tien',
    'trang_thai',
])]
class CongViecCaNhan extends Model
{
    protected $table = 'cong_viec_ca_nhan';

    public const TRANG_THAI = [
        'chua_hoan_thanh',
        'da_hoan_thanh',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'nguoi_phu_trach_viec_ids' => 'array',
            'thoi_gian_thuc_hien' => 'array',
            'muc_do_uu_tien' => 'integer',
        ];
    }

    public function nguoiGiaoViec(): BelongsTo
    {
        return $this->belongsTo(User::class, 'nguoi_giao_viec_id');
    }
}
