<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'ten_khach',
    'sdt',
    'ngay_hen_lich',
    'phu_trach_sale',
    'ghi_chu',
    'nguon_khach',
    'ngay_den_thuc_te',
    'trang_thai',
    'tra_cuu_hd',
    'hinh_thuc_dat_coc',
    'nguoi_tao',
])]
class KhachHangNoteKhachMoi extends Model
{
    protected $table = 'khach_hang_note_khach_moi';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'ngay_hen_lich' => 'date:Y-m-d',
            'ngay_den_thuc_te' => 'date:Y-m-d',
            'phu_trach_sale' => 'array',
        ];
    }

    public function nguoiTaoUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'nguoi_tao');
    }
}
