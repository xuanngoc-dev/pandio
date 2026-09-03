<?php

namespace App\Models;

use App\Models\Concerns\HasPublicMediaUrls;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'hinh_anh',
    'user_id',
    'phong_ban_ids',
    'vai_tro_id',
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
    use HasPublicMediaUrls;

    protected $table = 'nhan_vien';

    /**
     * @var list<string>
     */
    protected $appends = ['phong_bans'];

    /**
     * @return list<string>
     */
    protected function mediaUrlAttributes(): array
    {
        return ['hinh_anh'];
    }

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
            'luong_chinh_sua_anh' => 'Lương chỉnh sửa ảnh',
            'luong_dung_video' => 'Lương dựng video',
            'phu_cap_xang' => 'Phụ cấp xăng',
            'phu_cap_an_trua' => 'Phụ cấp ăn trưa',
            'phu_cap_dien_thoai' => 'Phụ cấp điện thoại',
            'phu_cap_nha_o' => 'Phụ cấp nhà ở',
            'phu_cap_thu_bay_va_chu_nhat' => 'Phụ cấp thứ 7/chủ nhật',
            'phu_cap_di_lam_ngay_nghi' => 'Phụ cấp đi làm ngày nghỉ',
            'thuong_chuyen_can' => 'Thưởng chuyên cần',
            'chuyen_can_khong_nghi' => 'Chuyên cần không nghỉ',
            'chuyen_can_nghi_1_ngay' => 'Chuyên cần nghỉ 1 ngày',
            'chuyen_can_nghi_2_ngay' => 'Chuyên cần nghỉ 2 ngày',
            'chuyen_can_nghi_3_ngay' => 'Chuyên cần nghỉ 3 ngày',
            'hoa_hong_hop_dong_sddv' => 'Hoa hồng HĐ sử dụng dịch vụ (%)',
            'hoa_hong_hop_dong_trang_phuc' => 'Hoa hồng HĐ trang phục (%)',
            'phi_xu_ly_hd_thue_trang_phuc' => 'Phí xử lý HĐ thuê trang phục',
            'luong_theo_dich_vu' => 'Lương theo dịch vụ',
        ];
    }

    /**
     * Vai trò có đơn giá theo điểm trong luong_theo_dich_vu.
     *
     * @return list<string>
     */
    public static function salaryDichVuRoles(): array
    {
        return ['chup', 'make', 'quay_phim'];
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
     * Giá trị mặc định theo từng khoản lương/thưởng/phụ cấp.
     *
     * @return array<string, float|int>
     */
    public static function salaryFieldDefaultValues(): array
    {
        return [
            'phu_cap_thu_bay_va_chu_nhat' => 0,
            'phu_cap_di_lam_ngay_nghi' => 0,
            'chuyen_can_khong_nghi' => 0,
            'chuyen_can_nghi_1_ngay' => 0,
            'chuyen_can_nghi_2_ngay' => 0,
            'chuyen_can_nghi_3_ngay' => 0,
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
            'vai_tro_id' => 'integer',
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

    /**
     * Đơn giá chụp/make/quay phim theo số điểm và loại dịch vụ (ma_dich_vu).
     *
     * @param  'chup'|'make'|'quay_phim'  $role
     */
    public function getLuongTheoDichVu(string $role, int $diem, int|string|null $loaiId = null, float $default = 0.0): float
    {
        $items = $this->luong_thuong_phu_cap['luong_theo_dich_vu']['items'] ?? [];
        if (! is_array($items) || $items === []) {
            return $default;
        }

        $list = array_is_list($items) ? $items : array_values($items);
        $level = (string) min(3, max(1, $diem));

        $pick = static function (array $item) use ($role, $level, $default): float {
            $map = $item[$role] ?? [];
            if (! is_array($map)) {
                return $default;
            }
            $value = $map[$level] ?? $map[(int) $level] ?? null;

            return $value !== null && $value !== '' ? (float) $value : $default;
        };

        // Có loại quay chụp → chỉ lấy đúng bản ghi ma_dich_vu / id trùng.
        if ($loaiId !== null && $loaiId !== '') {
            foreach ($list as $item) {
                if (! is_array($item)) {
                    continue;
                }
                $id = $item['ma_dich_vu']
                    ?? $item['id']
                    ?? $item['danh_muc_loai_quay_chup_id']
                    ?? null;
                if ($id !== null && $id !== '' && (string) $id === (string) $loaiId) {
                    return $pick($item);
                }
            }

            return $default;
        }

        foreach ($list as $item) {
            if (! is_array($item)) {
                continue;
            }
            $value = $pick($item);
            if ($value > 0) {
                return $value;
            }
        }

        $first = $list[0] ?? null;

        return is_array($first) ? $pick($first) : $default;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function vaiTro(): BelongsTo
    {
        return $this->belongsTo(VaiTro::class, 'vai_tro_id');
    }
}
