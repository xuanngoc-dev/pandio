<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'ma_hop_dong',
    'loai_hop_dong_id',
    'ten_khach_hang',
    'sdt_khach_hang',
    'dia_chi',
    'kenh_tiep_can',
    'thong_tin_hop_dong',
    'thong_tin_dieu_phoi',
    'ket_qua_hop_dong',
    'nguoi_tao_id',
    'nguoi_tham_gia_ids',
    'trang_thai',
    'tong_tien',
    'phat_sinh',
    'chiet_khau',
    'ma_giam_gia',
    'khuyen_mai_theo_ma_giam_gia',
    'tong_tien_khach_phai_thanh_toan',
    'tien_coc',
    'so_tien_thanh_toan_lan_1',
    'so_tien_thanh_toan_lan_2',
    'so_tien_thanh_toan_lan_3',
    'thoi_gian_thanh_toan_lan_1',
    'thoi_gian_thanh_toan_lan_2',
    'thoi_gian_thanh_toan_lan_3',
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

    /** Field nhân sự nội bộ trong từng buổi (gia_tri = mảng user id). */
    public const DIEU_PHOI_STAFF_KEYS = ['tho_chup', 'tho_make', 'tho_edit', 'quay_phim', 'tho_dung_video'];

    /** Nhân sự tiền kỳ: thợ chụp / thợ make / quay phim. */
    public const DIEU_PHOI_TIEN_KY_STAFF_KEYS = ['tho_chup', 'tho_make', 'quay_phim'];

    /** Nhân sự hậu kỳ: thợ edit / thợ dựng video. */
    public const DIEU_PHOI_HAU_KY_STAFF_KEYS = ['tho_edit', 'tho_dung_video'];

    /** Tổng số ảnh chỉnh sửa từ combo, lưu ở envelope thong_tin_dieu_phoi. */
    public const SO_ANH_CHINH_SUA_KEY = 'so_anh_chinh_sua';

    /** Trạng thái workflow điều phối, lưu ở envelope thong_tin_dieu_phoi. */
    public const TRANG_THAI_DIEU_PHOI_KEY = 'trang_thai_dieu_phoi';

    /** Thời điểm chuyển hoàn tất sản xuất, lưu ở envelope thong_tin_dieu_phoi. */
    public const THOI_GIAN_HOAN_TAT_SAN_XUAT_KEY = 'thoi_gian_hoan_tat_san_xuat';

    /** Note trạng thái thợ shop, lưu ở envelope thong_tin_dieu_phoi. */
    public const NOTE_THO_SHOP_KEY = 'note_tho_shop';

    public const NOTE_THO_SHOP_VALUES = [
        'doi_moi',
        'hen_qua',
        'can_sua_lai',
        'doi_check',
        'doi_in',
        'da_in',
        'phong_su_cuoi',
        'lay_truoc_anh_phong',
        'sua_truoc_anh_thiep',
        'con_anh_sua',
        'con_album',
        'con_slide',
    ];

    public const TRANG_THAI_DIEU_PHOI_CHO_NHAN = 'cho_nhan';

    public const TRANG_THAI_DIEU_PHOI_TIEN_KY = 'tien_ky';

    public const TRANG_THAI_DIEU_PHOI_HAU_KY = 'hau_ky';

    public const TRANG_THAI_DIEU_PHOI_GUI_IN = 'gui_in';

    public const TRANG_THAI_DIEU_PHOI_HOAN_TAT_SAN_XUAT = 'hoan_tat_san_xuat';

    public const TRANG_THAI_DIEU_PHOI_LATER = [
        'hau_ky',
        'gui_in',
        'hoan_tat_san_xuat',
        'dang_xu_ly',
        'gui_khach_kiem_tra',
        'san_xuat_in_an',
        'cho_nghiem_thu',
        'hoan_thanh',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'thong_tin_hop_dong' => 'array',
            'thong_tin_dieu_phoi' => 'array',
            'ket_qua_hop_dong' => 'array',
            'nguoi_tham_gia_ids' => 'array',
            'tong_tien' => 'integer',
            'phat_sinh' => 'integer',
            'chiet_khau' => 'integer',
            'khuyen_mai_theo_ma_giam_gia' => 'integer',
            'tong_tien_khach_phai_thanh_toan' => 'integer',
            'tien_coc' => 'integer',
            'so_tien_thanh_toan_lan_1' => 'integer',
            'so_tien_thanh_toan_lan_2' => 'integer',
            'so_tien_thanh_toan_lan_3' => 'integer',
            'thoi_gian_thanh_toan_lan_1' => 'datetime',
            'thoi_gian_thanh_toan_lan_2' => 'datetime',
            'thoi_gian_thanh_toan_lan_3' => 'datetime',
            'han_thanh_toan_lan_2' => 'date',
            'han_thanh_toan_lan_3' => 'date',
        ];
    }

    /**
     * Cấu trúc mặc định ket_qua_hop_dong (mỗi key: ten, mo_ta, gia_tri).
     *
     * @return array<string, array{ten: string, mo_ta: mixed, gia_tri: mixed}>
     */
    public static function defaultKetQuaHopDong(): array
    {
        return [
            'trang_thai' => [
                'ten' => 'Trạng thái',
                'mo_ta' => null,
                'gia_tri' => null,
            ],
            'link_file_le' => [
                'ten' => 'Link file lẻ',
                'mo_ta' => null,
                'gia_tri' => null,
                'thoi_gian_up_file' => null,
            ],
            'link_file_in' => [
                'ten' => 'Link file in',
                'mo_ta' => null,
                'gia_tri' => null,
                'thoi_gian_up_file' => null,
            ],
            'link_file_goc' => [
                'ten' => 'Link file gốc',
                'mo_ta' => null,
                'gia_tri' => null,
                'thoi_gian_up_file' => null,
            ],
            'link_giao_san_pham' => [
                'ten' => 'Link giao sản phẩm',
                'mo_ta' => null,
                'gia_tri' => null,
            ],
            'y_kien_khach_hang' => [
                'ten' => 'Ý kiến khách hàng',
                'mo_ta' => null,
                'gia_tri' => null,
            ],
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

    public function combos(): HasMany
    {
        return $this->hasMany(HopDongDongSddvCombo::class, 'ma_hop_dong_id');
    }

    public function dichVu(): HasMany
    {
        return $this->hasMany(HopDongDongSddvDichVu::class, 'ma_hop_dong_id');
    }

    public function concepts(): HasMany
    {
        return $this->hasMany(HopDongDongSddvConcept::class, 'ma_hop_dong_id');
    }

    public function trangPhucs(): HasMany
    {
        return $this->hasMany(HopDongDongSddvTrangPhuc::class, 'ma_hop_dong_id');
    }

    /**
     * Lấy danh sách buổi chụp từ thong_tin_dieu_phoi.danh_sach_buoi_chup.
     *
     * @return list<array<string, mixed>>
     */
    public static function normalizeDieuPhoiSessions(mixed $raw): array
    {
        if (! is_array($raw) || ! isset($raw['danh_sach_buoi_chup']) || ! is_array($raw['danh_sach_buoi_chup'])) {
            return [];
        }

        $sessions = [];
        foreach ($raw['danh_sach_buoi_chup'] as $item) {
            if (is_array($item) && $item !== [] && ! array_is_list($item)) {
                $sessions[] = $item;
            }
        }

        return $sessions;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function dieuPhoiSessions(): array
    {
        return self::normalizeDieuPhoiSessions($this->thong_tin_dieu_phoi);
    }

    public static function dieuPhoiGiaTri(?array $session, string $key): mixed
    {
        $item = $session[$key] ?? null;
        if (! is_array($item)) {
            return null;
        }

        return $item['gia_tri'] ?? null;
    }

    public static function trangThaiDieuPhoi(mixed $raw): ?string
    {
        if (! is_array($raw)) {
            return null;
        }

        $value = $raw[self::TRANG_THAI_DIEU_PHOI_KEY] ?? null;
        if ($value === null || $value === '') {
            return null;
        }

        return (string) $value;
    }

    /**
     * Hợp đồng đã gán thợ nội bộ hoặc thợ ngoài ở bất kỳ buổi nào.
     */
    public static function hasAssignedDieuPhoiStaff(mixed $raw): bool
    {
        foreach (self::normalizeDieuPhoiSessions($raw) as $session) {
            foreach (self::DIEU_PHOI_STAFF_KEYS as $key) {
                $value = self::dieuPhoiGiaTri($session, $key);
                if (! is_array($value)) {
                    if ($value !== null && $value !== '') {
                        return true;
                    }
                } else {
                    foreach ($value as $id) {
                        if ($id !== null && $id !== '') {
                            return true;
                        }
                    }
                }

                $ngoai = self::dieuPhoiGiaTri($session, $key.'_ngoai');
                if (is_string($ngoai) && trim($ngoai) !== '') {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * User có id nằm trong field nhân sự nội bộ của ít nhất một buổi.
     *
     * @param  list<string>  $keys
     */
    public static function userIsDieuPhoiStaff(mixed $raw, int $userId, array $keys): bool
    {
        foreach (self::normalizeDieuPhoiSessions($raw) as $session) {
            foreach ($keys as $key) {
                $value = self::dieuPhoiGiaTri($session, $key);
                $ids = is_array($value) ? $value : (($value !== null && $value !== '') ? [$value] : []);
                foreach ($ids as $id) {
                    if ($id !== null && $id !== '' && (int) $id === $userId) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
     * Gán thợ lần đầu → tien_ky. Không ghi đè workflow đã đi tiếp.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public static function withTienKyIfStaffAssigned(array $payload, ?string $existingStatus = null): array
    {
        if (! self::hasAssignedDieuPhoiStaff($payload)) {
            return $payload;
        }

        $current = self::trangThaiDieuPhoi($payload) ?? $existingStatus;
        if (in_array($current, self::TRANG_THAI_DIEU_PHOI_LATER, true)) {
            $payload[self::TRANG_THAI_DIEU_PHOI_KEY] = $current;

            return $payload;
        }

        $payload[self::TRANG_THAI_DIEU_PHOI_KEY] = self::TRANG_THAI_DIEU_PHOI_TIEN_KY;

        return $payload;
    }
}
