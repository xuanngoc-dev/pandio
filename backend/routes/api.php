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
use App\Http\Controllers\Api\DanhMucLoaiQuayChupController;
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
use App\Http\Controllers\Api\KhachHangController;
use App\Http\Controllers\Api\KhachHangNoteKhachMoiController;
use App\Http\Controllers\Api\NhaCungCapTrangPhucController;
use App\Http\Controllers\Api\PhieuThuChiController;
use App\Http\Controllers\Api\TrangPhucController;
use App\Http\Controllers\Api\DangKyCaLamViecController;
use App\Http\Controllers\Api\DiemDanhController;
use App\Http\Controllers\Api\IpDiemDanhController;
use App\Http\Controllers\Api\LoaiHopDongController;
use App\Http\Controllers\Api\TinhLuongController;
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
|
| apiResource tạo 5 endpoint REST:
|   GET    /{resource}           index    — danh sách
|   POST   /{resource}           store    — tạo mới
|   GET    /{resource}/{id}      show     — chi tiết
|   PUT    /{resource}/{id}      update   — cập nhật (PATCH cũng được)
|   DELETE /{resource}/{id}      destroy  — xóa
*/

// ---------------------------------------------------------------------------
// Auth công khai (không cần token)
// ---------------------------------------------------------------------------
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']); // Đăng ký tài khoản mới
    Route::post('/login', [AuthController::class, 'login']);       // Đăng nhập, trả token Sanctum
});

// Form đánh giá — công khai cho khách hàng điền theo slug (không cần đăng nhập)
Route::get('/public/form-danh-gia/{slug}', [CauHinhFormDanhGiaMauController::class, 'showBySlug']);

// Auth bảo vệ bởi Sanctum — thiếu/sai token → 401 JSON; tài khoản không active → chặn
Route::middleware(['auth:sanctum', EnsureUserIsActive::class])->group(function () {

    // --- Phiên đăng nhập ---
    Route::post('/auth/logout', [AuthController::class, 'logout']); // Đăng xuất, thu hồi token hiện tại
    Route::get('/user', [AuthController::class, 'me']);             // Thông tin user đang đăng nhập

    // --- Nhân sự (users + hồ sơ nhan_vien) ---
    Route::post('/users/upload-hinh-anh', [UserController::class, 'uploadHinhAnh']); // Upload ảnh nhân sự
    Route::apiResource('users', UserController::class); // CRUD tài khoản + hồ sơ nhân viên

    // --- Xin nghỉ phép ---
    Route::post('/xin-nghi-phep/bulk-duyet', [XinNghiPhepController::class, 'bulkDuyet']);           // Duyệt hàng loạt
    Route::post('/xin-nghi-phep/bulk-tu-choi', [XinNghiPhepController::class, 'bulkTuChoi']);       // Từ chối hàng loạt
    Route::post('/xin-nghi-phep/{xin_nghi_phep}/duyet', [XinNghiPhepController::class, 'duyet']);    // Duyệt 1 đơn
    Route::post('/xin-nghi-phep/{xin_nghi_phep}/tu-choi', [XinNghiPhepController::class, 'tuChoi']); // Từ chối 1 đơn
    Route::apiResource('xin-nghi-phep', XinNghiPhepController::class); // CRUD đơn xin nghỉ / đi muộn / về sớm

    // --- Đăng ký ca làm việc ---
    Route::post('/dang-ky-ca-lam-viec/sync-tuan', [DangKyCaLamViecController::class, 'syncTuan']); // Đồng bộ ca theo tuần (ghi đè khoảng ngày)
    Route::apiResource('dang-ky-ca-lam-viec', DangKyCaLamViecController::class); // CRUD đăng ký ca của nhân viên

    // --- Điểm danh ---
    Route::get('/diem-danh/today', [DiemDanhController::class, 'today']);           // Trạng thái checkin/checkout hôm nay của tôi
    Route::get('/diem-danh/ho-context', [DiemDanhController::class, 'hoContext']);   // Ngữ cảnh điểm danh hộ (ca, phép, bản ghi sẵn có)
    Route::post('/diem-danh/ho', [DiemDanhController::class, 'diemDanhHo']);         // Điểm danh hộ cho nhân viên khác
    Route::post('/diem-danh/checkin', [DiemDanhController::class, 'checkin']);       // Check-in (giờ vào)
    Route::post('/diem-danh/checkout', [DiemDanhController::class, 'checkout']);     // Check-out (giờ ra)
    Route::get('/diem-danh', [DiemDanhController::class, 'index']);                 // Danh sách lịch sử điểm danh

    // --- Tính lương ---
    Route::get('/tinh-luong/chi-tiet-theo-ngay', [TinhLuongController::class, 'chiTietTheoNgay']); // Bảng lương chi tiết từng ngày (user hiện tại)
    Route::get('/tinh-luong/tong-hop', [TinhLuongController::class, 'tongHop']);                   // Lương tổng hợp theo tháng (danh sách nhân viên)

    // --- Phòng ban ---
    Route::get('phong-ban/{phong_ban}/nhan-vien', [PhongBanController::class, 'nhanVien']);                       // Danh sách nhân viên trong phòng ban
    Route::delete('phong-ban/{phong_ban}/nhan-vien/{nhan_vien}', [PhongBanController::class, 'removeNhanVien']); // Gỡ nhân viên khỏi phòng ban
    Route::apiResource('phong-ban', PhongBanController::class); // CRUD phòng ban

    // --- IP điểm danh (whitelist IP được phép chấm công) ---
    Route::apiResource('ip-diem-danh', IpDiemDanhController::class); // CRUD IP được phép điểm danh

    // --- Cấu hình thông tin studio ---
    Route::post('/cau-hinh-thong-tin-studio/upload-logo', [CauHinhThongTinStudioController::class, 'uploadLogo']); // Upload logo studio
    Route::apiResource('cau-hinh-thong-tin-studio', CauHinhThongTinStudioController::class); // CRUD thông tin studio (tên, địa chỉ, logo…)

    // --- Cấu hình tài khoản thanh toán (STK nhận tiền) ---
    Route::apiResource('cau-hinh-tai-khoan-thanh-toan', CauHinhTaiKhoanThanhToanController::class); // CRUD tài khoản ngân hàng / ví

    // --- Cấu hình chi nhánh ---
    Route::apiResource('cau-hinh-chi-nhanh', CauHinhChiNhanhController::class); // CRUD chi nhánh studio

    // --- Cấu hình giờ làm việc ---
    Route::apiResource('cau-hinh-gio-lam-viec', CauHinhGioLamViecController::class); // CRUD khung giờ làm việc

    // --- Cấu hình ngày nghỉ ---
    Route::apiResource('cau-hinh-ngay-nghi', CauHinhNgayNghiController::class); // CRUD ngày nghỉ lễ / ngày nghỉ studio

    // --- Cấu hình ca làm việc ---
    Route::apiResource('cau-hinh-ca-lam-viec', CauHinhCaLamViecController::class); // CRUD ca (sáng/chiều/tối, giờ bắt đầu–kết thúc)

    // --- Vai trò (chức danh nhân sự) ---
    Route::apiResource('vai-tro', VaiTroController::class); // CRUD vai trò / chức danh

    // --- Thông báo hệ thống ---
    Route::apiResource('danh-muc-loai-thong-bao', DanhMucLoaiThongBaoController::class); // CRUD loại thông báo
    Route::get('he-thong-thong-bao/cua-toi', [HeThongThongBaoController::class, 'cuaToi']);                         // Hộp thư thông báo của tôi
    Route::post('he-thong-thong-bao/da-doc-tat-ca', [HeThongThongBaoController::class, 'danhDauTatCaDaDoc']);       // Đánh dấu đã đọc tất cả
    Route::post('he-thong-thong-bao/{he_thong_thong_bao}/da-doc', [HeThongThongBaoController::class, 'danhDauDaDoc']); // Đánh dấu đã đọc 1 thông báo
    Route::post('he-thong-thong-bao/{he_thong_thong_bao}/xoa-cua-toi', [HeThongThongBaoController::class, 'xoaCuaToi']); // Xóa thông báo khỏi hộp thư của tôi
    Route::apiResource('he-thong-thong-bao', HeThongThongBaoController::class); // CRUD thông báo (admin gửi)

    // --- Loại hợp đồng khách hàng (ký với khách, không phải nhân viên) ---
    Route::apiResource('loai-hop-dong', LoaiHopDongController::class); // CRUD loại HĐ dịch vụ (cưới, maternity…)

    // --- Form đánh giá mẫu ---
    Route::apiResource('cau-hinh-form-danh-gia-mau', CauHinhFormDanhGiaMauController::class); // CRUD form đánh giá (admin)

    // --- Danh mục nguồn khách ---
    Route::apiResource('danh-muc-nguon-khach', DanhMucNguonKhachController::class); // CRUD nguồn khách (Facebook, giới thiệu…)

    // --- Danh mục loại quay chụp ---
    Route::apiResource('danh-muc-loai-quay-chup', DanhMucLoaiQuayChupController::class); // CRUD loại quay/chụp

    // --- Tiện ích thời tiết ---
    Route::get('tien-ich-thoi-tiet', [TienIchThoiTietController::class, 'index']);     // Danh sách thời tiết đã lưu
    Route::post('tien-ich-thoi-tiet/sync', [TienIchThoiTietController::class, 'sync']); // Đồng bộ hàng loạt (trùng ngày thì ghi đè)

    // --- Cấu hình JSON động (singleton: mã giảm giá, setting linh hoạt…) ---
    Route::get('/cau-hinh-json', [CauHinhJsonController::class, 'show']);   // Lấy cấu hình JSON hiện tại
    Route::put('/cau-hinh-json', [CauHinhJsonController::class, 'update']); // Cập nhật / merge key cấu hình JSON

    // --- Concept ---
    Route::post('/concept/upload-hinh-anh', [ConceptController::class, 'uploadHinhAnh']); // Upload ảnh concept
    Route::apiResource('concept', ConceptController::class);                 // CRUD concept chụp
    Route::apiResource('danh-muc-concept', DanhMucConceptController::class); // CRUD danh mục concept

    // --- Trang phục ---
    Route::post('/trang-phuc/upload-hinh-anh', [TrangPhucController::class, 'uploadHinhAnh']); // Upload ảnh trang phục
    Route::get('/trang-phuc/{trang_phuc}/lich-cho-thue', [TrangPhucController::class, 'lichChoThue']); // Lịch đang cho thuê của 1 bộ đồ
    Route::apiResource('trang-phuc', TrangPhucController::class);                       // CRUD trang phục
    Route::apiResource('danh-muc-trang-phuc', DanhMucTrangPhucController::class);       // CRUD danh mục trang phục
    Route::apiResource('nha-cung-cap-trang-phuc', NhaCungCapTrangPhucController::class); // CRUD nhà cung cấp trang phục

    // Đặt mua trang phục
    Route::post('/dat-mua-trang-phuc/bulk-duyet', [DatMuaTrangPhucController::class, 'bulkDuyet']);                     // Duyệt hàng loạt đơn đặt mua
    Route::post('/dat-mua-trang-phuc/bulk-huy-duyet', [DatMuaTrangPhucController::class, 'bulkHuyDuyet']);             // Hủy duyệt hàng loạt
    Route::post('/dat-mua-trang-phuc/{dat_mua_trang_phuc}/duyet', [DatMuaTrangPhucController::class, 'duyet']);         // Duyệt 1 đơn đặt mua
    Route::post('/dat-mua-trang-phuc/{dat_mua_trang_phuc}/huy-duyet', [DatMuaTrangPhucController::class, 'huyDuyet']); // Hủy duyệt 1 đơn
    Route::apiResource('dat-mua-trang-phuc', DatMuaTrangPhucController::class); // CRUD đơn đặt mua trang phục

    // Hợp đồng cho thuê trang phục
    Route::post('/hop-dong-cho-thue-trang-phuc/khoi-tao', [HopDongChoThueTrangPhucController::class, 'khoiTao']); // Tạo HĐ nháp + sinh mã HDTTP
    Route::post('/hop-dong-cho-thue-trang-phuc/{hop_dong_cho_thue_trang_phuc}/thanh-toan', [HopDongChoThueTrangPhucController::class, 'thanhToan']); // Ghi nhận thanh toán HĐ thuê đồ
    Route::apiResource('hop-dong-cho-thue-trang-phuc', HopDongChoThueTrangPhucController::class); // CRUD hợp đồng cho thuê trang phục

    // Hợp đồng sử dụng dịch vụ (HĐ cưới / chụp)
    Route::get('/hop-dong-su-dung-dich-vu/cong-viec-cua-toi', [HopDongSuDungDichVuController::class, 'congViecCuaToi']);     // Công việc điều phối được gán cho tôi
    Route::get('/hop-dong-su-dung-dich-vu/lich-chup-make', [HopDongSuDungDichVuController::class, 'lichChupMake']);         // Lịch chụp/make theo khoảng ngày
    Route::get('/hop-dong-su-dung-dich-vu/lich-chup-make/chi-tiet', [HopDongSuDungDichVuController::class, 'lichChupMakeChiTiet']); // Chi tiết 1 slot lịch chụp/make
    Route::post('/hop-dong-su-dung-dich-vu/khoi-tao', [HopDongSuDungDichVuController::class, 'khoiTao']);                   // Tạo HĐ nháp + sinh mã
    Route::post('/hop-dong-su-dung-dich-vu/kiem-tra-ma-giam-gia', [HopDongSuDungDichVuController::class, 'kiemTraMaGiamGia']); // Kiểm tra mã giảm giá, trả số tiền giảm
    Route::post('/hop-dong-su-dung-dich-vu/{hop_dong_su_dung_dich_vu}/nhan-cong-viec', [HopDongSuDungDichVuController::class, 'nhanCongViec']); // Nhân viên nhận việc điều phối
    Route::post('/hop-dong-su-dung-dich-vu/{hop_dong_su_dung_dich_vu}/ket-qua-hop-dong', [HopDongSuDungDichVuController::class, 'capNhatKetQuaHopDong']); // Cập nhật kết quả HĐ (file, trạng thái sản xuất)
    Route::post('/hop-dong-su-dung-dich-vu/{hop_dong_su_dung_dich_vu}/ngay-dieu-phoi', [HopDongSuDungDichVuController::class, 'capNhatNgayDieuPhoi']); // Cập nhật ngày trả file lẻ / file in / khách hẹn qua (tiền kỳ & hậu kỳ)
    Route::post('/hop-dong-su-dung-dich-vu/{hop_dong_su_dung_dich_vu}/chuyen-hau-ky', [HopDongSuDungDichVuController::class, 'chuyenHauKy']); // Tiền kỳ → hậu kỳ (cần file gốc)
    Route::post('/hop-dong-su-dung-dich-vu/{hop_dong_su_dung_dich_vu}/chuyen-gui-in', [HopDongSuDungDichVuController::class, 'chuyenGuiIn']); // Hậu kỳ → gửi in (cần file lẻ + file in)
    Route::post('/hop-dong-su-dung-dich-vu/{hop_dong_su_dung_dich_vu}/chuyen-hoan-tat-san-xuat', [HopDongSuDungDichVuController::class, 'chuyenHoanTatSanXuat']); // Gửi in → hoàn tất sản xuất
    Route::post('/hop-dong-su-dung-dich-vu/{hop_dong_su_dung_dich_vu}/gui-khach-kiem-tra', [HopDongSuDungDichVuController::class, 'guiKhachKiemTra']); // Gửi khách kiểm tra sản phẩm
    Route::post('/hop-dong-su-dung-dich-vu/{hop_dong_su_dung_dich_vu}/xu-ly-khach-kiem-tra', [HopDongSuDungDichVuController::class, 'xuLyKhachKiemTra']); // Xử lý phản hồi khách sau kiểm tra
    Route::post('/hop-dong-su-dung-dich-vu/{hop_dong_su_dung_dich_vu}/ban-giao', [HopDongSuDungDichVuController::class, 'banGiao']); // Bàn giao sản phẩm
    Route::post('/hop-dong-su-dung-dich-vu/{hop_dong_su_dung_dich_vu}/xu-ly-nghiem-thu', [HopDongSuDungDichVuController::class, 'xuLyNghiemThu']); // Xử lý nghiệm thu (hoàn thành / làm lại)
    Route::post('/hop-dong-su-dung-dich-vu/{hop_dong_su_dung_dich_vu}/thanh-toan', [HopDongSuDungDichVuController::class, 'thanhToan']); // Ghi nhận thanh toán HĐ dịch vụ
    Route::post('/hop-dong-su-dung-dich-vu/{hop_dong_su_dung_dich_vu}/doi-trang-thai', [HopDongSuDungDichVuController::class, 'doiTrangThai']); // Đổi trạng thái vận hành (hủy, tất toán, khách đồng ý…)
    Route::apiResource('hop-dong-su-dung-dich-vu', HopDongSuDungDichVuController::class); // CRUD hợp đồng sử dụng dịch vụ

    // --- Công việc cá nhân ---
    Route::apiResource('cong-viec-ca-nhan', CongViecCaNhanController::class); // CRUD việc nội bộ (giao / phụ trách)

    // --- Report quảng cáo ---
    Route::apiResource('report-quang-cao', ReportQuangCaoController::class); // CRUD báo cáo chi phí / hiệu quả quảng cáo

    // --- Khách hàng (gom từ note / HĐ dịch vụ / HĐ thuê trang phục) ---
    Route::get('khach-hang', [KhachHangController::class, 'index']); // Danh sách khách hàng gom theo SĐT

    // --- Note khách mới (lịch khách hàng) ---
    Route::get('khach-hang-note-khach-moi/lich', [KhachHangNoteKhachMoiController::class, 'lich']); // Lịch theo khoảng ngày (hẹn lịch / đến)
    Route::get('khach-hang-note-khach-moi/lich/chi-tiet', [KhachHangNoteKhachMoiController::class, 'lichChiTiet']); // Chi tiết lịch theo ngày
    Route::apiResource('khach-hang-note-khach-moi', KhachHangNoteKhachMoiController::class); // CRUD ghi chú / lịch khách tiềm năng

    // --- Tài chính — kế toán thuế ---
    Route::apiResource('hang-muc-loai-thu-chi', HangMucLoaiThuChiController::class); // CRUD hạng mục loại thu/chi
    Route::post('/phieu-thu-chi/bulk-delete', [PhieuThuChiController::class, 'bulkDestroy']);           // Xóa hàng loạt phiếu
    Route::post('/phieu-thu-chi/bulk-update-status', [PhieuThuChiController::class, 'bulkUpdateStatus']); // Đổi trạng thái hàng loạt phiếu
    Route::apiResource('phieu-thu-chi', PhieuThuChiController::class); // CRUD phiếu thu / phiếu chi

    // --- Dịch vụ ---
    Route::apiResource('dich-vu-loai-dich-vu', DichVuLoaiDichVuController::class);                 // CRUD loại dịch vụ
    Route::apiResource('dich-vu-danh-sach-dich-vu-le', DichVuDanhSachDichVuLeController::class);   // CRUD dịch vụ lẻ (item đơn)
    // Param rút ngắn vì Symfony giới hạn tên biến route ≤ 32 ký tự (tên mặc định dài 35).
    // Binding: {nhom_dich_vu} ↔ DichVuDanhSachDichNhomDichVu (bảng dich_vu_danh_sach_dich_nhom_dich_vu).
    Route::apiResource('dich-vu-danh-sach-dich-nhom-dich-vu', DichVuDanhSachDichNhomDichVuController::class)
        ->parameters(['dich-vu-danh-sach-dich-nhom-dich-vu' => 'nhom_dich_vu']); // CRUD nhóm dịch vụ (combo)
});
