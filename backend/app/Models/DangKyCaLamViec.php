<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'ca_lam_id',
    'nguoi_dung_id',
    'ngay_lam',
])]
class DangKyCaLamViec extends Model
{
    protected $table = 'dang_ky_ca_lam_viec';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'ngay_lam' => 'date',
        ];
    }

    public function caLam(): BelongsTo
    {
        return $this->belongsTo(CauHinhCaLamViec::class, 'ca_lam_id');
    }

    public function nguoiDung(): BelongsTo
    {
        return $this->belongsTo(User::class, 'nguoi_dung_id');
    }
}
