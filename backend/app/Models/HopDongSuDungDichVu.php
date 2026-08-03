<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'ma_hop_dong',
    'loai_hop_dong_id',
    'ten_khach_hang',
    'sdt_khach_hang',
    'dia_chi',
    'kenh_tiep_can',
    'thong_tin_hop_dong',
    'nguoi_tao_id',
    'nguoi_tham_gia_ids',
    'trang_thai',
    'tong_tien',
    'chiet_khau',
    'ma_giam_gia',
    'khuyen_mai_theo_ma_giam_gia',
    'tien_coc',
    'hinh_thuc_coc',
    'han_thanh_toan_lan_2',
    'han_thanh_toan_lan_3',
    'qua_tang_kem',
    'yeu_cau_dac_biet',
    'ghi_chu_sale',
    'luot_gioi_thieu',
])]
class HopDongSuDungDichVu extends Model
{
    protected $table = 'hop_dong_su_dung_dich_vu';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'thong_tin_hop_dong' => 'array',
            'nguoi_tham_gia_ids' => 'array',
            'tong_tien' => 'integer',
            'chiet_khau' => 'integer',
            'khuyen_mai_theo_ma_giam_gia' => 'integer',
            'tien_coc' => 'integer',
            'han_thanh_toan_lan_2' => 'date',
            'han_thanh_toan_lan_3' => 'date',
        ];
    }

    /**
     * Sinh mã hợp đồng theo quy tắc: HDSDDV_DDMMYYYY{id}
     */
    public static function buildMaHopDong(int $id, ?\DateTimeInterface $date = null): string
    {
        $date ??= now();

        return 'HDSDDV_'.$date->format('dmY').$id;
    }

    public function loaiHopDong(): BelongsTo
    {
        return $this->belongsTo(LoaiHopDong::class, 'loai_hop_dong_id');
    }

    public function nguoiTao(): BelongsTo
    {
        return $this->belongsTo(User::class, 'nguoi_tao_id');
    }
}
