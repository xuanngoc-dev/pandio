<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'hinh_anh',
    'user_id',
    'phong_ban_ids',
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
    'luong_thuong_phu_cap',
])]
class NhanVien extends Model
{
    protected $table = 'nhan_vien';

    /**
     * @var list<string>
     */
    protected $appends = ['phong_bans'];

    /**
     * Định nghĩa mặc định các khoản lương/thưởng/phụ cấp.
     *
     * @return array<string, string>
     */
    public static function salaryFieldDefinitions(): array
    {
        return [
            'luong_cung' => 'Lương cứng',
            'luong_mem' => 'Lương mềm',
            'phu_cap' => 'Phụ cấp',
            'luong_1_gio' => 'Lương 1 giờ',
            'luong_tang_ca_1_gio' => 'Lương tăng ca 1 giờ',
            'phu_cap_xang' => 'Phụ cấp xăng',
            'phu_cap_an_trua' => 'Phụ cấp ăn trưa',
            'phu_cap_dien_thoai' => 'Phụ cấp điện thoại',
            'phu_cap_nha_o' => 'Phụ cấp nhà ở',
            'thuong_chuyen_can' => 'Thưởng chuyên cần',
            'hoa_hong_hop_dong_sddv' => 'Hoa hồng HĐ sử dụng dịch vụ',
            'hoa_hong_hop_dong_trang_phuc' => 'Hoa hồng HĐ trang phục',
            'chup_1_diem' => 'Chụp 1 điểm',
            'chup_2_diem' => 'Chụp 2 điểm',
            'chup_3_diem' => 'Chụp 3 điểm',
            'make_1_diem' => 'Make 1 điểm',
            'make_2_diem' => 'Make 2 điểm',
            'make_3_diem' => 'Make 3 điểm',
            'phi_xu_ly_hd_thue_trang_phuc' => 'Phí xử lý HĐ thuê trang phục',
        ];
    }

    /**
     * Note mặc định theo từng khoản lương/thưởng/phụ cấp.
     *
     * @return array<string, string>
     */
    public static function salaryFieldNotes(): array
    {
        return [
            'luong_1_gio' => 'Dành cho part time',
            'luong_tang_ca_1_gio' => 'Dành cho cả part_time và full_time',
        ];
    }

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
            'so_nguoi_phu_thuoc' => 'integer',
            'luong_thuong_phu_cap' => 'array',
            'phong_ban_ids' => 'array',
        ];
    }

    /**
     * Danh sách phòng ban theo phong_ban_ids (giữ thứ tự đã chọn).
     *
     * @return Attribute<list<array<string, mixed>>, never>
     */
    protected function phongBans(): Attribute
    {
        return Attribute::get(function () {
            $ids = $this->phong_ban_ids ?? [];
            if (! is_array($ids) || $ids === []) {
                return [];
            }

            $ids = array_values(array_unique(array_map('intval', $ids)));

            return PhongBan::query()
                ->whereIn('id', $ids)
                ->get()
                ->sortBy(fn (PhongBan $pb) => array_search($pb->id, $ids, true))
                ->values()
                ->all();
        });
    }

    /**
     * Lấy giá trị số của một khoản trong luong_thuong_phu_cap.
     */
    public function getLuongValue(string $key, float $default = 0.0): float
    {
        $data = $this->luong_thuong_phu_cap;
        if (! is_array($data)) {
            return $default;
        }

        $value = $data[$key]['value'] ?? null;

        return $value !== null && $value !== '' ? (float) $value : $default;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
