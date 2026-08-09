<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CongViecCaNhanController;
use App\Http\Controllers\Api\CauHinhCaLamViecController;
use App\Http\Controllers\Api\ConceptController;
use App\Http\Controllers\Api\CauHinhChiNhanhController;
use App\Http\Controllers\Api\CauHinhFormDanhGiaMauController;
use App\Http\Controllers\Api\CauHinhGioLamViecController;
use App\Http\Controllers\Api\CauHinhJsonController;
use App\Http\Controllers\Api\CauHinhNgayNghiController;
use App\Http\Controllers\Api\CauHinhTaiKhoanThanhToanController;
use App\Http\Controllers\Api\CauHinhThongTinStudioController;
use App\Http\Controllers\Api\DanhMucConceptController;
use App\Http\Controllers\Api\DanhMucLoaiThongBaoController;
use App\Http\Controllers\Api\DanhMucNguonKhachController;
use App\Http\Controllers\Api\HeThongThongBaoController;
use App\Http\Controllers\Api\TienIchThoiTietController;
use App\Http\Controllers\Api\DanhMucTrangPhucController;
use App\Http\Controllers\Api\DichVuDanhSachDichNhomDichVuController;
use App\Http\Controllers\Api\DichVuDanhSachDichVuLeController;
use App\Http\Controllers\Api\DichVuLoaiDichVuController;
use App\Http\Controllers\Api\DatMuaTrangPhucController;
use App\Http\Controllers\Api\HopDongChoThueTrangPhucController;
use App\Http\Controllers\Api\HopDongSuDungDichVuController;
use App\Http\Controllers\Api\HangMucLoaiThuChiController;
use App\Http\Controllers\Api\KhachHangNoteKhachMoiController;
use App\Http\Controllers\Api\NhaCungCapTrangPhucController;
use App\Http\Controllers\Api\PhieuThuChiController;
use App\Http\Controllers\Api\TrangPhucController;
use App\Http\Controllers\Api\DangKyCaLamViecController;
use App\Http\Controllers\Api\DiemDanhController;
use App\Http\Controllers\Api\IpDiemDanhController;
use App\Http\Controllers\Api\LoaiHopDongController;
use App\Http\Controllers\Api\PhongBanController;
use App\Http\Controllers\Api\ReportQuangCaoController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\VaiTroController;
use App\Http\Controllers\Api\XinNghiPhepController;
use App\Http\Middleware\EnsureUserIsActive;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
| Prefix mặc định: /api
*/

// Auth công khai (không cần token)
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
});

// Form đánh giá — công khai cho khách hàng điền
Route::get('/public/form-danh-gia/{slug}', [CauHinhFormDanhGiaMauController::class, 'showBySlug']);

// Auth bảo vệ bởi Sanctum — thiếu/sai token → 401 JSON, không cho gọi API
Route::middleware(['auth:sanctum', EnsureUserIsActive::class])->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'me']);

    // Nhân sự (users + nhan_vien)
    Route::post('/users/upload-hinh-anh', [UserController::class, 'uploadHinhAnh']);
    Route::apiResource('users', UserController::class);

    // Xin nghỉ phép
    Route::post('/xin-nghi-phep/bulk-duyet', [XinNghiPhepController::class, 'bulkDuyet']);
    Route::post('/xin-nghi-phep/bulk-tu-choi', [XinNghiPhepController::class, 'bulkTuChoi']);
    Route::post('/xin-nghi-phep/{xin_nghi_phep}/duyet', [XinNghiPhepController::class, 'duyet']);
    Route::post('/xin-nghi-phep/{xin_nghi_phep}/tu-choi', [XinNghiPhepController::class, 'tuChoi']);
    Route::apiResource('xin-nghi-phep', XinNghiPhepController::class);

    // Đăng ký ca làm việc
    Route::post('/dang-ky-ca-lam-viec/sync-tuan', [DangKyCaLamViecController::class, 'syncTuan']);
    Route::apiResource('dang-ky-ca-lam-viec', DangKyCaLamViecController::class);

    // Điểm danh
    Route::get('/diem-danh/today', [DiemDanhController::class, 'today']);
    Route::get('/diem-danh/ho-context', [DiemDanhController::class, 'hoContext']);
    Route::post('/diem-danh/ho', [DiemDanhController::class, 'diemDanhHo']);
    Route::post('/diem-danh/checkin', [DiemDanhController::class, 'checkin']);
    Route::post('/diem-danh/checkout', [DiemDanhController::class, 'checkout']);
    Route::get('/diem-danh', [DiemDanhController::class, 'index']);

    // Phòng ban
    Route::get('phong-ban/{phong_ban}/nhan-vien', [PhongBanController::class, 'nhanVien']);
    Route::delete('phong-ban/{phong_ban}/nhan-vien/{nhan_vien}', [PhongBanController::class, 'removeNhanVien']);
    Route::apiResource('phong-ban', PhongBanController::class);

    // IP điểm danh
    Route::apiResource('ip-diem-danh', IpDiemDanhController::class);

    // Cấu hình thông tin studio
    Route::post('/cau-hinh-thong-tin-studio/upload-logo', [CauHinhThongTinStudioController::class, 'uploadLogo']);
    Route::apiResource('cau-hinh-thong-tin-studio', CauHinhThongTinStudioController::class);

    // Cấu hình tài khoản thanh toán
    Route::apiResource('cau-hinh-tai-khoan-thanh-toan', CauHinhTaiKhoanThanhToanController::class);

    // Cấu hình chi nhánh
    Route::apiResource('cau-hinh-chi-nhanh', CauHinhChiNhanhController::class);

    // Cấu hình giờ làm việc
    Route::apiResource('cau-hinh-gio-lam-viec', CauHinhGioLamViecController::class);

    // Cấu hình ngày nghỉ
    Route::apiResource('cau-hinh-ngay-nghi', CauHinhNgayNghiController::class);

    // Cấu hình ca làm việc
    Route::apiResource('cau-hinh-ca-lam-viec', CauHinhCaLamViecController::class);

    // Vai trò (chức danh nhân sự)
    Route::apiResource('vai-tro', VaiTroController::class);

    // Loại thông báo hệ thống
    Route::apiResource('danh-muc-loai-thong-bao', DanhMucLoaiThongBaoController::class);
    Route::get('he-thong-thong-bao/cua-toi', [HeThongThongBaoController::class, 'cuaToi']);
    Route::post('he-thong-thong-bao/da-doc-tat-ca', [HeThongThongBaoController::class, 'danhDauTatCaDaDoc']);
    Route::post('he-thong-thong-bao/{he_thong_thong_bao}/da-doc', [HeThongThongBaoController::class, 'danhDauDaDoc']);
    Route::post('he-thong-thong-bao/{he_thong_thong_bao}/xoa-cua-toi', [HeThongThongBaoController::class, 'xoaCuaToi']);
    Route::apiResource('he-thong-thong-bao', HeThongThongBaoController::class);

    // Loại hợp đồng khách hàng (ký với khách, không phải nhân viên)
    Route::apiResource('loai-hop-dong', LoaiHopDongController::class);

    // Form đánh giá mẫu
    Route::apiResource('cau-hinh-form-danh-gia-mau', CauHinhFormDanhGiaMauController::class);

    // Danh mục nguồn khách
    Route::apiResource('danh-muc-nguon-khach', DanhMucNguonKhachController::class);

    // Tiện ích thời tiết
    Route::get('tien-ich-thoi-tiet', [TienIchThoiTietController::class, 'index']);
    Route::post('tien-ich-thoi-tiet/sync', [TienIchThoiTietController::class, 'sync']);

    // Cấu hình JSON động
    Route::get('/cau-hinh-json', [CauHinhJsonController::class, 'show']);
    Route::put('/cau-hinh-json', [CauHinhJsonController::class, 'update']);

    // Concept
    Route::post('/concept/upload-hinh-anh', [ConceptController::class, 'uploadHinhAnh']);
    Route::apiResource('concept', ConceptController::class);
    Route::apiResource('danh-muc-concept', DanhMucConceptController::class);

    // Trang phục
    Route::post('/trang-phuc/upload-hinh-anh', [TrangPhucController::class, 'uploadHinhAnh']);
    Route::get('/trang-phuc/{trang_phuc}/lich-cho-thue', [TrangPhucController::class, 'lichChoThue']);
    Route::apiResource('trang-phuc', TrangPhucController::class);
    Route::apiResource('danh-muc-trang-phuc', DanhMucTrangPhucController::class);
    Route::apiResource('nha-cung-cap-trang-phuc', NhaCungCapTrangPhucController::class);
    Route::post('/dat-mua-trang-phuc/bulk-duyet', [DatMuaTrangPhucController::class, 'bulkDuyet']);
    Route::post('/dat-mua-trang-phuc/bulk-huy-duyet', [DatMuaTrangPhucController::class, 'bulkHuyDuyet']);
    Route::post('/dat-mua-trang-phuc/{dat_mua_trang_phuc}/duyet', [DatMuaTrangPhucController::class, 'duyet']);
    Route::post('/dat-mua-trang-phuc/{dat_mua_trang_phuc}/huy-duyet', [DatMuaTrangPhucController::class, 'huyDuyet']);
    Route::apiResource('dat-mua-trang-phuc', DatMuaTrangPhucController::class);
    Route::post('/hop-dong-cho-thue-trang-phuc/khoi-tao', [HopDongChoThueTrangPhucController::class, 'khoiTao']);
    Route::post('/hop-dong-cho-thue-trang-phuc/{hop_dong_cho_thue_trang_phuc}/thanh-toan', [HopDongChoThueTrangPhucController::class, 'thanhToan']);
    Route::apiResource('hop-dong-cho-thue-trang-phuc', HopDongChoThueTrangPhucController::class);
    Route::get('/hop-dong-su-dung-dich-vu/cong-viec-cua-toi', [HopDongSuDungDichVuController::class, 'congViecCuaToi']);
    Route::get('/hop-dong-su-dung-dich-vu/lich-chup-make', [HopDongSuDungDichVuController::class, 'lichChupMake']);
    Route::get('/hop-dong-su-dung-dich-vu/lich-chup-make/chi-tiet', [HopDongSuDungDichVuController::class, 'lichChupMakeChiTiet']);
    Route::post('/hop-dong-su-dung-dich-vu/khoi-tao', [HopDongSuDungDichVuController::class, 'khoiTao']);
    Route::post('/hop-dong-su-dung-dich-vu/{hop_dong_su_dung_dich_vu}/nhan-cong-viec', [HopDongSuDungDichVuController::class, 'nhanCongViec']);
    Route::post('/hop-dong-su-dung-dich-vu/{hop_dong_su_dung_dich_vu}/ket-qua-hop-dong', [HopDongSuDungDichVuController::class, 'capNhatKetQuaHopDong']);
    Route::post('/hop-dong-su-dung-dich-vu/{hop_dong_su_dung_dich_vu}/gui-khach-kiem-tra', [HopDongSuDungDichVuController::class, 'guiKhachKiemTra']);
    Route::post('/hop-dong-su-dung-dich-vu/{hop_dong_su_dung_dich_vu}/xu-ly-khach-kiem-tra', [HopDongSuDungDichVuController::class, 'xuLyKhachKiemTra']);
    Route::post('/hop-dong-su-dung-dich-vu/{hop_dong_su_dung_dich_vu}/ban-giao', [HopDongSuDungDichVuController::class, 'banGiao']);
    Route::post('/hop-dong-su-dung-dich-vu/{hop_dong_su_dung_dich_vu}/xu-ly-nghiem-thu', [HopDongSuDungDichVuController::class, 'xuLyNghiemThu']);
    Route::post('/hop-dong-su-dung-dich-vu/{hop_dong_su_dung_dich_vu}/thanh-toan', [HopDongSuDungDichVuController::class, 'thanhToan']);
    Route::post('/hop-dong-su-dung-dich-vu/{hop_dong_su_dung_dich_vu}/doi-trang-thai', [HopDongSuDungDichVuController::class, 'doiTrangThai']);
    Route::apiResource('hop-dong-su-dung-dich-vu', HopDongSuDungDichVuController::class);

    // Công việc cá nhân
    Route::apiResource('cong-viec-ca-nhan', CongViecCaNhanController::class);

    // Report quảng cáo
    Route::apiResource('report-quang-cao', ReportQuangCaoController::class);

    // Note khách mới (lịch khách hàng)
    Route::apiResource('khach-hang-note-khach-moi', KhachHangNoteKhachMoiController::class);

    // Tài chính — kế toán thuế
    Route::apiResource('hang-muc-loai-thu-chi', HangMucLoaiThuChiController::class);
    Route::post('/phieu-thu-chi/bulk-delete', [PhieuThuChiController::class, 'bulkDestroy']);
    Route::post('/phieu-thu-chi/bulk-update-status', [PhieuThuChiController::class, 'bulkUpdateStatus']);
    Route::apiResource('phieu-thu-chi', PhieuThuChiController::class);

    // Dịch vụ
    Route::apiResource('dich-vu-loai-dich-vu', DichVuLoaiDichVuController::class);
    Route::apiResource('dich-vu-danh-sach-dich-vu-le', DichVuDanhSachDichVuLeController::class);
    // Param rút ngắn vì Symfony giới hạn tên biến route ≤ 32 ký tự (tên mặc định dài 35).
    // Binding: {nhom_dich_vu} ↔ DichVuDanhSachDichNhomDichVu (bảng dich_vu_danh_sach_dich_nhom_dich_vu).
    Route::apiResource('dich-vu-danh-sach-dich-nhom-dich-vu', DichVuDanhSachDichNhomDichVuController::class)
        ->parameters(['dich-vu-danh-sach-dich-nhom-dich-vu' => 'nhom_dich_vu']);
});
