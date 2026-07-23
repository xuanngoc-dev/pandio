<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PhongBan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PhongBanController extends Controller
{
    /**
     * Danh sách phòng ban — phân trang + tìm kiếm.
     *
     * Query: page, per_page, keyword
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'keyword' => ['sometimes', 'nullable', 'string', 'max:255'],
        ]);

        $perPage = $validated['per_page'] ?? 10;
        $keyword = trim((string) ($validated['keyword'] ?? ''));

        $query = PhongBan::query()
            ->when($keyword !== '', function ($q) use ($keyword) {
                $q->where(function ($inner) use ($keyword) {
                    $inner->where('ten_phong_ban', 'like', "%{$keyword}%")
                        ->orWhere('ma_phong_ban', 'like', "%{$keyword}%")
                        ->orWhere('truong_phong', 'like', "%{$keyword}%");
                });
            })
            ->orderByDesc('id');

        return response()->json($query->paginate($perPage));
    }

    /**
     * Chi tiết một phòng ban.
     */
    public function show(PhongBan $phong_ban): JsonResponse
    {
        return response()->json($phong_ban);
    }

    /**
     * Tạo phòng ban mới.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ten_phong_ban' => ['required', 'string', 'max:255'],
            'ma_phong_ban' => ['required', 'string', 'max:50', 'unique:phong_ban,ma_phong_ban'],
            'truong_phong' => ['nullable', 'string', 'max:255'],
            'mo_ta' => ['nullable', 'string'],
            'ghi_chu' => ['nullable', 'string'],
        ]);

        $phongBan = PhongBan::create($validated);

        return response()->json($phongBan, 201);
    }

    /**
     * Cập nhật phòng ban.
     */
    public function update(Request $request, PhongBan $phong_ban): JsonResponse
    {
        $validated = $request->validate([
            'ten_phong_ban' => ['required', 'string', 'max:255'],
            'ma_phong_ban' => [
                'required',
                'string',
                'max:50',
                Rule::unique('phong_ban', 'ma_phong_ban')->ignore($phong_ban->id),
            ],
            'truong_phong' => ['nullable', 'string', 'max:255'],
            'mo_ta' => ['nullable', 'string'],
            'ghi_chu' => ['nullable', 'string'],
        ]);

        $phong_ban->update($validated);

        return response()->json($phong_ban->fresh());
    }

    /**
     * Xóa phòng ban.
     */
    public function destroy(PhongBan $phong_ban): JsonResponse
    {
        $phong_ban->delete();

        return response()->json(['message' => 'Đã xóa phòng ban.']);
    }
}
