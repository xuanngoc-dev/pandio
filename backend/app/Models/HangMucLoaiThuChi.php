<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'ten_hang_muc',
    'ghi_chu',
    'trang_thai',
])]
class HangMucLoaiThuChi extends Model
{
    protected $table = 'hang_muc_loai_thu_chi';

    public function phieuThuChi(): HasMany
    {
        return $this->hasMany(PhieuThuChi::class, 'hang_muc_id');
    }
}
