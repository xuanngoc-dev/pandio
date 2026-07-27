<?php

namespace App\Http\Controllers\Api;

use App\Models\Concept;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ConceptController extends BaseApiController
{
    /**
     * Danh sách concept — phân trang + tìm kiếm.
     *
     * Query: page, per_page, keyword, loai_concept, trang_thai
     */
    public function index(Request $request): JsonResponse
    {
        return $this->handleApi(function () use ($request) {
            $validated = $request->validate([
                'page' => ['sometimes', 'integer', 'min:1'],
                'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
                'keyword' => ['sometimes', 'nullable', 'string', 'max:255'],
                'loai_concept' => ['sometimes', 'nullable', 'integer', 'exists:danh_muc_concept,id'],
                'trang_thai' => ['sometimes', 'nullable', Rule::in(['dang_su_dung', 'ngung_su_dung'])],
            ]);

            $perPage = $validated['per_page'] ?? 10;
            $keyword = trim((string) ($validated['keyword'] ?? ''));

            $query = Concept::query()
                ->with('danhMuc:id,ten_danh_muc')
                ->when($keyword !== '', function ($q) use ($keyword) {
                    $q->where(function ($inner) use ($keyword) {
                        $inner->where('ma_concept', 'like', "%{$keyword}%")
                            ->orWhere('ten_concept', 'like', "%{$keyword}%")
                            ->orWhere('dia_diem', 'like', "%{$keyword}%")
                            ->orWhere('mo_ta', 'like', "%{$keyword}%");
                    });
                })
                ->when(! empty($validated['loai_concept'] ?? null), function ($q) use ($validated) {
                    $q->where('loai_concept', $validated['loai_concept']);
                })
                ->when(! empty($validated['trang_thai'] ?? null), function ($q) use ($validated) {
                    $q->where('trang_thai', $validated['trang_thai']);
                })
                ->orderByDesc('id');

            return response()->json($query->paginate($perPage));

        }, 'lấy danh sách concept');
    }

    /**
     * Chi tiết một concept.
     */
    public function show(Concept $concept): JsonResponse
    {
        return $this->handleApi(function () use ($concept) {
            $concept->load('danhMuc:id,ten_danh_muc');

            return response()->json($concept);

        }, 'lấy chi tiết concept');
    }

    /**
     * Upload hình ảnh concept.
     */
    public function uploadHinhAnh(Request $request): JsonResponse
    {
        return $this->handleApi(function () use ($request) {
            $validated = $request->validate([
                'hinh_anh' => ['required', 'image', 'mimes:jpeg,jpg,png,webp,gif', 'max:2048'],
            ], [
                'hinh_anh.required' => 'Vui lòng chọn hình ảnh.',
                'hinh_anh.image' => 'File phải là hình ảnh.',
                'hinh_anh.mimes' => 'Chỉ chấp nhận jpeg, jpg, png, webp, gif.',
                'hinh_anh.max' => 'Hình ảnh tối đa 2MB.',
            ]);

            $path = $validated['hinh_anh']->store('concept', 'public');

            return response()->json([
                'path' => $path,
                'url' => Storage::disk('public')->url($path),
            ], 201);

        }, 'upload hình ảnh concept');
    }

    /**
     * Tạo concept mới.
     */
    public function store(Request $request): JsonResponse
    {
        return $this->handleApi(function () use ($request) {
            $validated = $this->validatePayload($request);

            $concept = Concept::create($validated);
            $concept->load('danhMuc:id,ten_danh_muc');

            return response()->json($concept, 201);

        }, 'tạo concept');
    }

    /**
     * Cập nhật concept.
     */
    public function update(Request $request, Concept $concept): JsonResponse
    {
        return $this->handleApi(function () use ($request, $concept) {
            $validated = $this->validatePayload($request, $concept);

            $concept->update($validated);

            return response()->json($concept->fresh()->load('danhMuc:id,ten_danh_muc'));

        }, 'cập nhật concept');
    }

    /**
     * Xóa concept.
     */
    public function destroy(Concept $concept): JsonResponse
    {
        return $this->handleApi(function () use ($concept) {
            if ($concept->hinh_anh) {
                Storage::disk('public')->delete($concept->hinh_anh);
            }

            $concept->delete();

            return response()->json(['message' => 'Đã xóa concept.']);

        }, 'xóa concept');
    }

    /**
     * @return array<string, mixed>
     */
    private function validatePayload(Request $request, ?Concept $concept = null): array
    {
        return $request->validate([
            'hinh_anh' => ['nullable', 'string', 'max:500'],
            'loai_concept' => ['required', 'integer', 'exists:danh_muc_concept,id'],
            'ma_concept' => [
                'required',
                'string',
                'max:50',
                Rule::unique('concept', 'ma_concept')->ignore($concept?->id),
            ],
            'ten_concept' => ['required', 'string', 'max:255'],
            'dia_diem' => ['nullable', 'string', 'max:255'],
            'trang_thai' => ['required', Rule::in(['dang_su_dung', 'ngung_su_dung'])],
            'mo_ta' => ['nullable', 'string'],
        ]);
    }
}
