<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CauHinhTaiKhoanThanhToan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class CauHinhTaiKhoanThanhToanController extends Controller
{
    /**
     * Danh sách tài khoản thanh toán — phân trang + tìm kiếm.
     *
     * Query: page, per_page, keyword, mac_dinh
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'keyword' => ['sometimes', 'nullable', 'string', 'max:255'],
            'mac_dinh' => ['sometimes', 'nullable', 'string', Rule::in(['co', 'khong'])],
        ]);

        $perPage = $validated['per_page'] ?? 10;
        $keyword = trim((string) ($validated['keyword'] ?? ''));
        $macDinh = $validated['mac_dinh'] ?? null;

        $query = CauHinhTaiKhoanThanhToan::query()
            ->when($keyword !== '', function ($q) use ($keyword) {
                $q->where(function ($inner) use ($keyword) {
                    $inner->where('ngan_hang', 'like', "%{$keyword}%")
                        ->orWhere('so_tai_khoan', 'like', "%{$keyword}%")
                        ->orWhere('chu_tai_khoan', 'like', "%{$keyword}%")
                        ->orWhere('chi_nhanh', 'like', "%{$keyword}%");
                });
            })
            ->when($macDinh, fn ($q) => $q->where('mac_dinh', $macDinh))
            ->orderByDesc('created_at')
            ->orderByDesc('id');

        return response()->json($query->paginate($perPage));
    }

    /**
     * Chi tiết một tài khoản thanh toán.
     */
    public function show(CauHinhTaiKhoanThanhToan $cau_hinh_tai_khoan_thanh_toan): JsonResponse
    {
        return response()->json($cau_hinh_tai_khoan_thanh_toan);
    }

    /**
     * Tạo tài khoản thanh toán mới.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $this->validatePayload($request);

        $taiKhoan = DB::transaction(function () use ($validated) {
            if (($validated['mac_dinh'] ?? 'khong') === 'co') {
                CauHinhTaiKhoanThanhToan::query()->update(['mac_dinh' => 'khong']);
            }

            return CauHinhTaiKhoanThanhToan::create($validated);
        });

        return response()->json($taiKhoan, 201);
    }

    /**
     * Cập nhật tài khoản thanh toán.
     */
    public function update(Request $request, CauHinhTaiKhoanThanhToan $cau_hinh_tai_khoan_thanh_toan): JsonResponse
    {
        $validated = $this->validatePayload($request);

        $taiKhoan = DB::transaction(function () use ($cau_hinh_tai_khoan_thanh_toan, $validated) {
            if (($validated['mac_dinh'] ?? 'khong') === 'co') {
                CauHinhTaiKhoanThanhToan::query()
                    ->where('id', '!=', $cau_hinh_tai_khoan_thanh_toan->id)
                    ->update(['mac_dinh' => 'khong']);
            }

            $cau_hinh_tai_khoan_thanh_toan->update($validated);

            return $cau_hinh_tai_khoan_thanh_toan->fresh();
        });

        return response()->json($taiKhoan);
    }

    /**
     * Xóa tài khoản thanh toán.
     */
    public function destroy(CauHinhTaiKhoanThanhToan $cau_hinh_tai_khoan_thanh_toan): JsonResponse
    {
        $cau_hinh_tai_khoan_thanh_toan->delete();

        return response()->json(['message' => 'Đã xóa tài khoản thanh toán.']);
    }

    /**
     * @return array<string, mixed>
     */
    private function validatePayload(Request $request): array
    {
        return $request->validate([
            'ngan_hang' => ['required', 'string', 'max:255'],
            'so_tai_khoan' => ['required', 'string', 'max:100'],
            'chu_tai_khoan' => ['required', 'string', 'max:255'],
            'chi_nhanh' => ['nullable', 'string', 'max:255'],
            'mac_dinh' => ['required', 'string', Rule::in(['co', 'khong'])],
        ]);
    }
}
