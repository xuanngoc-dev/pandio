<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CauHinhCaLamViec;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CauHinhCaLamViecController extends Controller
{
    /**
     * Danh sách ca làm việc — phân trang + tìm kiếm.
     *
     * Query: page, per_page, keyword, trang_thai
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'keyword' => ['sometimes', 'nullable', 'string', 'max:255'],
            'trang_thai' => ['sometimes', 'nullable', 'string', Rule::in(['co', 'khong'])],
        ]);

        $perPage = $validated['per_page'] ?? 10;
        $keyword = trim((string) ($validated['keyword'] ?? ''));
        $trangThai = $validated['trang_thai'] ?? null;

        $query = CauHinhCaLamViec::query()
            ->when($keyword !== '', function ($q) use ($keyword) {
                $q->where(function ($inner) use ($keyword) {
                    $inner->where('ten_ca', 'like', "%{$keyword}%")
                        ->orWhere('ghi_chu', 'like', "%{$keyword}%");
                });
            })
            ->when($trangThai, fn ($q) => $q->where('trang_thai', $trangThai))
            ->orderBy('gio_bat_dau')
            ->orderByDesc('id');

        return response()->json($query->paginate($perPage));
    }

    /**
     * Chi tiết một ca làm việc.
     */
    public function show(CauHinhCaLamViec $cau_hinh_ca_lam_viec): JsonResponse
    {
        return response()->json($cau_hinh_ca_lam_viec);
    }

    /**
     * Tạo ca làm việc mới.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $this->validatePayload($request);

        $item = CauHinhCaLamViec::create($validated);

        return response()->json($item, 201);
    }

    /**
     * Cập nhật ca làm việc.
     */
    public function update(Request $request, CauHinhCaLamViec $cau_hinh_ca_lam_viec): JsonResponse
    {
        $validated = $this->validatePayload($request);

        $cau_hinh_ca_lam_viec->update($validated);

        return response()->json($cau_hinh_ca_lam_viec->fresh());
    }

    /**
     * Xóa ca làm việc.
     */
    public function destroy(CauHinhCaLamViec $cau_hinh_ca_lam_viec): JsonResponse
    {
        $cau_hinh_ca_lam_viec->delete();

        return response()->json(['message' => 'Đã xóa ca làm việc.']);
    }

    /**
     * @return array<string, mixed>
     */
    private function validatePayload(Request $request): array
    {
        return $request->validate([
            'ten_ca' => ['required', 'string', 'max:255'],
            'gio_bat_dau' => ['required', 'date_format:H:i'],
            'gio_ket_thuc' => ['required', 'date_format:H:i', 'after:gio_bat_dau'],
            'trang_thai' => ['required', 'string', Rule::in(['co', 'khong'])],
            'ghi_chu' => ['nullable', 'string', 'max:2000'],
        ]);
    }
}
