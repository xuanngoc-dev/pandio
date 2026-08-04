<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'ten_hop_dong',
    'ma_hop_dong',
    'noi_dung',
    'thong_tin_dieu_phoi',
    'trang_thai',
])]
/**
 * Loại hợp đồng ký với khách hàng (KHÔNG phải hợp đồng nhân viên).
 */
class LoaiHopDong extends Model
{
    protected $table = 'danh_muc_loai_hop_dong';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'noi_dung' => 'array',
            'thong_tin_dieu_phoi' => 'array',
        ];
    }
}
