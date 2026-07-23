<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'hinh_anh',
    'user_id',
    'phong_ban_id',
    'ngan_hang',
    'chi_nhanh',
    'so_tai_khoan',
    'chu_tai_khoan',
    'gioi_tinh',
    'ngay_sinh',
    'cccd',
    'vi_tri_lam_viec',
    'ngay_vao_cong_ty',
    'ngay_ky_hop_dong',
    'loai_nhan_vien',
    'loai_hop_dong',
    'cong_chuan',
    'tham_gia_bao_hiem',
    'so_nguoi_phu_thuoc',
    'luong_cung',
    'luong_mem',
    'phu_cap',
    'luong_co_ban',
    'luong_tang_ca',
    'phu_cap_xang',
    'phu_cap_an_trua',
    'phu_cap_dien_thoai',
    'phu_cap_nha_o',
    'thuong_chuyen_can',
    'hoa_hong_hop_dong_cuoi',
    'hoa_hong_hop_dong_trang_phuc',
])]
class NhanVien extends Model
{
    protected $table = 'nhan_vien';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'ngay_sinh' => 'date:Y-m-d',
            'ngay_vao_cong_ty' => 'date:Y-m-d',
            'ngay_ky_hop_dong' => 'date:Y-m-d',
            'tham_gia_bao_hiem' => 'boolean',
            'cong_chuan' => 'decimal:2',
            'luong_cung' => 'decimal:2',
            'luong_mem' => 'decimal:2',
            'phu_cap' => 'decimal:2',
            'luong_co_ban' => 'decimal:2',
            'luong_tang_ca' => 'decimal:2',
            'phu_cap_xang' => 'decimal:2',
            'phu_cap_an_trua' => 'decimal:2',
            'phu_cap_dien_thoai' => 'decimal:2',
            'phu_cap_nha_o' => 'decimal:2',
            'thuong_chuyen_can' => 'decimal:2',
            'hoa_hong_hop_dong_cuoi' => 'decimal:2',
            'hoa_hong_hop_dong_trang_phuc' => 'decimal:2',
            'so_nguoi_phu_thuoc' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function phongBan(): BelongsTo
    {
        return $this->belongsTo(PhongBan::class, 'phong_ban_id');
    }
}
