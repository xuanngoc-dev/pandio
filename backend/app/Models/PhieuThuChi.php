<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'nguoi_tao_id',
    'nguoi_duyet_id',
    'loai',
    'hang_muc_id',
    'so_tien',
    'ly_do',
    'trang_thai',
    'ngay_cap_nhat_trang_thai',
    'ghi_chu',
])]
class PhieuThuChi extends Model
{
    protected $table = 'phieu_thu_chi';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'so_tien' => 'integer',
            'ngay_cap_nhat_trang_thai' => 'datetime',
        ];
    }

    public function nguoiTao(): BelongsTo
    {
        return $this->belongsTo(User::class, 'nguoi_tao_id');
    }

    public function nguoiDuyet(): BelongsTo
    {
        return $this->belongsTo(User::class, 'nguoi_duyet_id');
    }

    public function hangMuc(): BelongsTo
    {
        return $this->belongsTo(HangMucLoaiThuChu::class, 'hang_muc_id');
    }
}
