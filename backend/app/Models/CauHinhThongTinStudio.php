<?php

namespace App\Models;

use App\Models\Concerns\HasPublicMediaUrls;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'ten_studio',
    'khau_hieu',
    'logo',
    'dia_chi',
    'email',
    'so_dien_thoai',
    'ma_so_thue',
    'mac_dinh',
])]
class CauHinhThongTinStudio extends Model
{
    use HasPublicMediaUrls;

    protected $table = 'cau_hinh_thong_tin_studio';

    /**
     * @return list<string>
     */
    protected function mediaUrlAttributes(): array
    {
        return ['logo'];
    }
}
