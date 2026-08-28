<?php

namespace App\Support;

use Illuminate\Database\QueryException;

/**
 * Chuyển QueryException (MySQL) thành HTTP status + message tiếng Việt cho FE.
 * Không bao giờ trả SQLSTATE / câu SQL thô ra client.
 */
class QueryExceptionMapper
{
    /** @var array<string, string> */
    private const TABLE_LABELS = [
        'users' => 'tài khoản',
        'nhan_vien' => 'nhân viên',
        'phong_ban' => 'phòng ban',
        'vai_tro' => 'vai trò',
        'danh_muc_loai_hop_dong' => 'loại hợp đồng',
        'loai_hop_dong' => 'loại hợp đồng',
        'hop_dong_su_dung_dich_vu' => 'hợp đồng dịch vụ',
        'hop_dong_dong_sddv_combos' => 'combo hợp đồng',
        'hop_dong_dong_sddv_dich_vu' => 'dịch vụ trong hợp đồng',
        'hop_dong_dong_sddv_concept' => 'concept trong hợp đồng',
        'hop_dong_dong_sddv_trang_phuc' => 'trang phục trong hợp đồng',
        'hop_dong_cho_thue_trang_phuc' => 'hợp đồng cho thuê trang phục',
        'hop_dong_cho_thue_trang_phuc_san_pham_cho_thue' => 'sản phẩm cho thuê',
        'dich_vu_loai_dich_vu' => 'loại dịch vụ',
        'dich_vu_danh_sach_dich_vu_le' => 'dịch vụ lẻ',
        'dich_vu_danh_sach_dich_nhom_dich_vu' => 'nhóm dịch vụ',
        'danh_muc_concept' => 'danh mục concept',
        'concept' => 'concept',
        'danh_muc_trang_phuc' => 'danh mục trang phục',
        'trang_phuc' => 'trang phục',
        'nha_cung_cap_trang_phuc' => 'nhà cung cấp trang phục',
        'dat_mua_trang_phuc' => 'đặt mua trang phục',
        'danh_muc_nguon_khach' => 'nguồn khách',
        'khach_hang_note_khach_moi' => 'note khách mới',
        'danh_muc_loai_thong_bao' => 'loại thông báo',
        'he_thong_thong_bao' => 'thông báo',
        'danh_muc_loai_quay_chup' => 'loại quay chụp',
        'hang_muc_loai_thu_chi' => 'hạng mục thu chi',
        'phieu_thu_chi' => 'phiếu thu chi',
        'cau_hinh_ca_lam_viec' => 'ca làm việc',
        'cau_hinh_gio_lam_viec' => 'giờ làm việc',
        'cau_hinh_ngay_nghi' => 'ngày nghỉ',
        'cau_hinh_chi_nhanh' => 'chi nhánh',
        'cau_hinh_tai_khoan_thanh_toan' => 'tài khoản thanh toán',
        'cau_hinh_thong_tin_studio' => 'thông tin studio',
        'cau_hinh_form_danh_gia_mau' => 'form đánh giá mẫu',
        'cau_hinh_json' => 'cấu hình JSON',
        'dang_ky_ca_lam_viec' => 'đăng ký ca làm việc',
        'diem_danh' => 'điểm danh',
        'ip_diem_danh' => 'IP điểm danh',
        'xin_nghi_phep' => 'đơn xin nghỉ phép',
        'cong_viec_ca_nhan' => 'công việc cá nhân',
        'report_quang_cao' => 'report quảng cáo',
        'tien_ich_thoi_tiet' => 'thời tiết',
        'personal_access_tokens' => 'token đăng nhập',
    ];

    /** @var array<string, string> */
    private const COLUMN_LABELS = [
        'email' => 'email',
        'username' => 'tên đăng nhập',
        'ma_hop_dong' => 'mã hợp đồng',
        'ma_dich_vu' => 'mã dịch vụ',
        'ma_nhom' => 'mã nhóm',
        'ma_nhan_vien' => 'mã nhân viên',
        'ten_hop_dong' => 'tên hợp đồng',
        'ten_dich_vu' => 'tên dịch vụ',
        'ten_nhom' => 'tên nhóm',
        'ten_vai_tro' => 'tên vai trò',
        'ten_phong_ban' => 'tên phòng ban',
        'ten_nguon_khach' => 'tên nguồn khách',
        'slug' => 'slug',
        'ip' => 'địa chỉ IP',
        'so_dien_thoai' => 'số điện thoại',
        'sdt' => 'số điện thoại',
        'ma' => 'mã',
    ];

    public static function status(QueryException $e): int
    {
        $sqlState = (string) ($e->errorInfo[0] ?? '');
        $driverCode = (int) ($e->errorInfo[1] ?? 0);

        if ($driverCode === 1452) {
            return 422;
        }

        if ($sqlState === '23000' || in_array($driverCode, [1062, 1451], true)) {
            return 409;
        }

        return 500;
    }

    public static function message(QueryException $e, string $action = 'thao tác'): string
    {
        $driverCode = (int) ($e->errorInfo[1] ?? 0);
        $sqlState = (string) ($e->errorInfo[0] ?? '');
        $raw = $e->getMessage();

        return match (true) {
            $driverCode === 1062 => self::duplicateMessage($raw, $action),
            $driverCode === 1451 => self::parentRowMessage($raw, $action),
            $driverCode === 1452 => self::childRowMessage($raw, $action),
            $sqlState === '23000' => "Không thể {$action} vì ràng buộc dữ liệu.",
            default => "Lỗi cơ sở dữ liệu khi {$action}. Vui lòng thử lại sau.",
        };
    }

    private static function duplicateMessage(string $raw, string $action): string
    {
        $column = self::extractDuplicateColumn($raw);
        if ($column !== null) {
            $label = self::COLUMN_LABELS[$column] ?? str_replace('_', ' ', $column);

            return "Giá trị {$label} đã tồn tại. Vui lòng chọn giá trị khác.";
        }

        return "Dữ liệu bị trùng khi {$action}.";
    }

    private static function parentRowMessage(string $raw, string $action): string
    {
        // Cannot delete parent — child table is the one in CONSTRAINT fails (`db`.`child`, ...)
        $child = self::extractConstraintTable($raw);
        if ($child !== null) {
            $label = self::tableLabel($child);

            return "Không thể {$action} vì đang được sử dụng bởi {$label}.";
        }

        return "Không thể {$action} vì dữ liệu đang được tham chiếu bởi bản ghi khác.";
    }

    private static function childRowMessage(string $raw, string $action): string
    {
        // Cannot add/update child — REFERENCES `parent` is the missing/invalid link
        $parent = self::extractReferencedTable($raw) ?? self::extractConstraintTable($raw);
        if ($parent !== null) {
            $label = self::tableLabel($parent);

            return "Dữ liệu liên kết không hợp lệ khi {$action} ({$label} không tồn tại hoặc không hợp lệ).";
        }

        return "Dữ liệu liên kết không hợp lệ khi {$action}.";
    }

    private static function extractDuplicateColumn(string $raw): ?string
    {
        // Duplicate entry 'x' for key 'table.column'
        if (preg_match("/for key ['`](?:[^'`]+\\.)?([^'`]+)['`]/i", $raw, $m)) {
            $key = $m[1];
            // users_email_unique → email; danh_muc_loai_hop_dong_ma_hop_dong_unique → ma_hop_dong
            if (preg_match('/(?:^|_)((?:ma|ten|so|sdt|email|username|slug|ip|phone)(?:_[a-z0-9]+)?)_(?:unique|index)$/i', $key, $col)) {
                return strtolower($col[1]);
            }
            if (str_contains($key, '.')) {
                return strtolower((string) strrchr($key, '.') ?: $key);
            }
            // fallback: strip table prefix + _unique/_index
            $stripped = preg_replace('/_unique$|_index$/i', '', $key) ?? $key;
            $parts = explode('_', $stripped);
            if (count($parts) >= 2) {
                // take last 1–3 segments as likely column name
                foreach ([3, 2, 1] as $n) {
                    if (count($parts) >= $n) {
                        $candidate = implode('_', array_slice($parts, -$n));
                        if (isset(self::COLUMN_LABELS[$candidate])) {
                            return $candidate;
                        }
                    }
                }
            }

            return strtolower($stripped);
        }

        return null;
    }

    private static function extractConstraintTable(string $raw): ?string
    {
        // ... fails (`db`.`table_name`, CONSTRAINT ...
        if (preg_match('/fails\s*\(\s*[`\'][^`\']*[`\']\s*\.\s*[`\']([^`\']+)[`\']/i', $raw, $m)) {
            return $m[1];
        }

        if (preg_match('/fails\s*\(\s*[`\']([^`\'.]+)[`\']/i', $raw, $m)) {
            return $m[1];
        }

        return null;
    }

    private static function extractReferencedTable(string $raw): ?string
    {
        // REFERENCES `parent_table` (`id`)
        if (preg_match('/REFERENCES\s*[`\']([^`\']+)[`\']/i', $raw, $m)) {
            return $m[1];
        }

        return null;
    }

    private static function tableLabel(string $table): string
    {
        return self::TABLE_LABELS[$table] ?? str_replace('_', ' ', $table);
    }
}
