<?php

use App\Http\Controllers\Api\AuthController;
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
use App\Http\Controllers\Api\DanhMucTrangPhucController;
use App\Http\Controllers\Api\DichVuDanhSachDichNhomDichVuController;
use App\Http\Controllers\Api\DichVuDanhSachDichVuLeController;
use App\Http\Controllers\Api\DichVuLoaiDichVuController;
use App\Http\Controllers\Api\DatMuaTrangPhucController;
use App\Http\Controllers\Api\HopDongChoThueTrangPhucController;
use App\Http\Controllers\Api\NhaCungCapTrangPhucController;
use App\Http\Controllers\Api\TrangPhucController;
use App\Http\Controllers\Api\DangKyCaLamViecController;
use App\Http\Controllers\Api\DiemDanhController;
use App\Http\Controllers\Api\IpDiemDanhController;
use App\Http\Controllers\Api\LoaiHopDongController;
use App\Http\Controllers\Api\PhongBanController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\VaiTroController;
use App\Http\Controllers\Api\XinNghiPhepController;
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

// Auth bảo vệ bởi Sanctum
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'me']);

    // Nhân sự (users + nhan_vien)
    Route::post('/users/upload-hinh-anh', [UserController::class, 'uploadHinhAnh']);
    Route::apiResource('users', UserController::class);

    // Xin nghỉ phép
    Route::post('/xin-nghi-phep/{xin_nghi_phep}/duyet', [XinNghiPhepController::class, 'duyet']);
    Route::post('/xin-nghi-phep/{xin_nghi_phep}/tu-choi', [XinNghiPhepController::class, 'tuChoi']);
    Route::apiResource('xin-nghi-phep', XinNghiPhepController::class);

    // Đăng ký ca làm việc
    Route::post('/dang-ky-ca-lam-viec/sync-tuan', [DangKyCaLamViecController::class, 'syncTuan']);
    Route::apiResource('dang-ky-ca-lam-viec', DangKyCaLamViecController::class);

    // Điểm danh
    Route::get('/diem-danh/today', [DiemDanhController::class, 'today']);
    Route::post('/diem-danh/checkin', [DiemDanhController::class, 'checkin']);
    Route::post('/diem-danh/checkout', [DiemDanhController::class, 'checkout']);
    Route::get('/diem-danh', [DiemDanhController::class, 'index']);

    // Phòng ban
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

    // Loại hợp đồng khách hàng (ký với khách, không phải nhân viên)
    Route::apiResource('loai-hop-dong', LoaiHopDongController::class);

    // Form đánh giá mẫu
    Route::apiResource('cau-hinh-form-danh-gia-mau', CauHinhFormDanhGiaMauController::class);

    // Cấu hình JSON động
    Route::get('/cau-hinh-json', [CauHinhJsonController::class, 'show']);
    Route::put('/cau-hinh-json', [CauHinhJsonController::class, 'update']);

    // Concept
    Route::post('/concept/upload-hinh-anh', [ConceptController::class, 'uploadHinhAnh']);
    Route::apiResource('concept', ConceptController::class);
    Route::apiResource('danh-muc-concept', DanhMucConceptController::class);

    // Trang phục
    Route::post('/trang-phuc/upload-hinh-anh', [TrangPhucController::class, 'uploadHinhAnh']);
    Route::apiResource('trang-phuc', TrangPhucController::class);
    Route::apiResource('danh-muc-trang-phuc', DanhMucTrangPhucController::class);
    Route::apiResource('nha-cung-cap-trang-phuc', NhaCungCapTrangPhucController::class);
    Route::apiResource('dat-mua-trang-phuc', DatMuaTrangPhucController::class);
    Route::apiResource('hop-dong-cho-thue-trang-phuc', HopDongChoThueTrangPhucController::class);

    // Dịch vụ
    Route::apiResource('dich-vu-loai-dich-vu', DichVuLoaiDichVuController::class);
    Route::apiResource('dich-vu-danh-sach-dich-vu-le', DichVuDanhSachDichVuLeController::class);
    // Param rút ngắn vì Symfony giới hạn tên biến route ≤ 32 ký tự (tên mặc định dài 35).
    // Binding: {nhom_dich_vu} ↔ DichVuDanhSachDichNhomDichVu (bảng dich_vu_danh_sach_dich_nhom_dich_vu).
    Route::apiResource('dich-vu-danh-sach-dich-nhom-dich-vu', DichVuDanhSachDichNhomDichVuController::class)
        ->parameters(['dich-vu-danh-sach-dich-nhom-dich-vu' => 'nhom_dich_vu']);
});
