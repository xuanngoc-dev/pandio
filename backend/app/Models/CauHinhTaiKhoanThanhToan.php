<?php

namespace App\Models;

use App\Models\Concerns\HasPublicMediaUrls;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'ngan_hang',
    'so_tai_khoan',
    'chu_tai_khoan',
    'chi_nhanh',
    'hinh_anh_logo',
    'mac_dinh',
    'trang_thai',
])]
class CauHinhTaiKhoanThanhToan extends Model
{
    use HasPublicMediaUrls;

    protected $table = 'cau_hinh_tai_khoan_thanh_toan';

    /**
     * @return list<string>
     */
    protected function mediaUrlAttributes(): array
    {
        return ['hinh_anh_logo'];
    }
}
