<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'nguoi_nhan_ids',
    'actor_id',
    'loai_thong_bao_id',
    'loai_mau_sac',
    'tieu_de',
    'noi_dung',
    'nguoi_nhan_da_doc_ids',
    'nguoi_dung_da_xoa_ids',
    'muc_do_uu_tien',
    'du_lieu',
])]
class HeThongThongBao extends Model
{
    protected $table = 'he_thong_thong_bao';

    public const MAU_SAC = [
        'red',
        'green',
        'yellow',
        'blue',
        'orange',
        'purple',
        'gray',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'nguoi_nhan_ids' => 'array',
            'nguoi_nhan_da_doc_ids' => 'array',
            'nguoi_dung_da_xoa_ids' => 'array',
            'du_lieu' => 'array',
            'muc_do_uu_tien' => 'integer',
        ];
    }

    public function loaiThongBao(): BelongsTo
    {
        return $this->belongsTo(DanhMucLoaiThongBao::class, 'loai_thong_bao_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
