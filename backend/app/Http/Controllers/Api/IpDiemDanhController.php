<?php

namespace App\Http\Controllers\Api;

use App\Models\IpDiemDanh;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class IpDiemDanhController extends BaseApiController
{
    /**
     * Danh sách IP điểm danh — phân trang + tìm kiếm.
     *
     * Query: page, per_page, keyword, trang_thai
     */
    public function index(Request $request): JsonResponse
    {
        return $this->handleApi(function () use ($request) {
            $validated = $request->validate([
                'page' => ['sometimes', 'integer', 'min:1'],
                'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
                'keyword' => ['sometimes', 'nullable', 'string', 'max:255'],
                'trang_thai' => ['sometimes', 'nullable', 'string', Rule::in(['active', 'inactive'])],
            ]);

            $perPage = $validated['per_page'] ?? 10;
            $keyword = trim((string) ($validated['keyword'] ?? ''));
            $trangThai = $validated['trang_thai'] ?? null;

            $query = IpDiemDanh::query()
                ->when($keyword !== '', function ($q) use ($keyword) {
                    $q->where(function ($inner) use ($keyword) {
                        $inner->where('ten_ip', 'like', "%{$keyword}%")
                            ->orWhere('dia_chi_ip', 'like', "%{$keyword}%")
                            ->orWhere('ghi_chu', 'like', "%{$keyword}%");
                    });
                })
                ->when($trangThai, fn ($q) => $q->where('trang_thai', $trangThai))
                ->orderByDesc('id');

            return response()->json($query->paginate($perPage));

        }, 'lấy danh sách IP điểm danh');
    }

    /**
     * Chi tiết một IP điểm danh.
     */
    public function show(IpDiemDanh $ip_diem_danh): JsonResponse
    {
        return $this->handleApi(function () use ($ip_diem_danh) {
            return response()->json($ip_diem_danh);

        }, 'lấy chi tiết IP điểm danh');
    }

    /**
     * Tạo IP điểm danh mới.
     */
    public function store(Request $request): JsonResponse
    {
        return $this->handleApi(function () use ($request) {
            $validated = $request->validate([
                'ten_ip' => ['required', 'string', 'max:255'],
                'dia_chi_ip' => ['required', 'string', 'max:45', 'unique:ip_diem_danh,dia_chi_ip'],
                'ghi_chu' => ['nullable', 'string'],
                'trang_thai' => ['required', 'string', Rule::in(['active', 'inactive'])],
            ]);

            $ipDiemDanh = IpDiemDanh::create($validated);

            return response()->json($ipDiemDanh, 201);

        }, 'tạo IP điểm danh');
    }

    /**
     * Cập nhật IP điểm danh.
     */
    public function update(Request $request, IpDiemDanh $ip_diem_danh): JsonResponse
    {
        return $this->handleApi(function () use ($request, $ip_diem_danh) {
            $validated = $request->validate([
                'ten_ip' => ['required', 'string', 'max:255'],
                'dia_chi_ip' => [
                    'required',
                    'string',
                    'max:45',
                    Rule::unique('ip_diem_danh', 'dia_chi_ip')->ignore($ip_diem_danh->id),
                ],
                'ghi_chu' => ['nullable', 'string'],
                'trang_thai' => ['required', 'string', Rule::in(['active', 'inactive'])],
            ]);

            $ip_diem_danh->update($validated);

            return response()->json($ip_diem_danh->fresh());

        }, 'cập nhật IP điểm danh');
    }

    /**
     * Xóa IP điểm danh.
     */
    public function destroy(IpDiemDanh $ip_diem_danh): JsonResponse
    {
        return $this->handleApi(function () use ($ip_diem_danh) {
            $ip_diem_danh->delete();

            return response()->json(['message' => 'Đã xóa IP điểm danh.']);

        }, 'xóa IP điểm danh');
    }
}
