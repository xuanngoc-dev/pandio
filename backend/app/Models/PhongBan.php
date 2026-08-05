<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'ten_phong_ban',
    'ma_phong_ban',
    'truong_phong',
    'mo_ta',
    'ghi_chu',
])]
class PhongBan extends Model
{
    protected $table = 'phong_ban';

    /**
     * Nhân viên thuộc phòng ban (phong_ban_ids JSON).
     */
    public function nhanViensQuery(): Builder
    {
        return NhanVien::query()->whereJsonContains('phong_ban_ids', (int) $this->id);
    }

    public function hasNhanVien(): bool
    {
        return $this->nhanViensQuery()->exists();
    }

    public function countNhanVien(): int
    {
        return $this->nhanViensQuery()->count();
    }
}
