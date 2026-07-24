<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'ngay_lam',
    'ca_lam_id',
    'gio_vao',
    'gio_ra',
    'di_muon',
    've_som',
    'thoi_gian_di_muon',
    'thoi_gian_ve_som',
    'tien_phat_di_muon',
    'tien_phat_ve_som',
    'ly_do',
    'ip_checkin',
    'ip_checkout',
    'gio_lam_co_ban',
    'gio_lam_tang_ca',
    'luong_co_ban',
    'luong_tang_ca',
    'ghi_chu',
])]
class DiemDanh extends Model
{
    protected $table = 'diem_danh';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'ngay_lam' => 'date',
            'thoi_gian_di_muon' => 'integer',
            'thoi_gian_ve_som' => 'integer',
            'tien_phat_di_muon' => 'decimal:2',
            'tien_phat_ve_som' => 'decimal:2',
            'gio_lam_co_ban' => 'decimal:2',
            'gio_lam_tang_ca' => 'decimal:2',
            'luong_co_ban' => 'decimal:2',
            'luong_tang_ca' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function caLam(): BelongsTo
    {
        return $this->belongsTo(CauHinhCaLamViec::class, 'ca_lam_id');
    }
}
