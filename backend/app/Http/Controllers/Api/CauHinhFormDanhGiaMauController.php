<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CauHinhFormDanhGiaMau;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CauHinhFormDanhGiaMauController extends Controller
{
    /**
     * Danh sách form đánh giá mẫu — phân trang + tìm kiếm.
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

        $query = CauHinhFormDanhGiaMau::query()
            ->when($keyword !== '', function ($q) use ($keyword) {
                $q->where(function ($inner) use ($keyword) {
                    $inner->where('ten_form', 'like', "%{$keyword}%")
                        ->orWhere('slug', 'like', "%{$keyword}%");
                });
            })
            ->orderByDesc('id');

        return response()->json($query->paginate($perPage));
    }

    /**
     * Chi tiết một form đánh giá mẫu.
     */
    public function show(CauHinhFormDanhGiaMau $cau_hinh_form_danh_gia_mau): JsonResponse
    {
        return response()->json($cau_hinh_form_danh_gia_mau);
    }

    /**
     * Lấy form đánh giá mẫu theo slug (công khai — cho khách hàng điền).
     */
    public function showBySlug(string $slug): JsonResponse
    {
        $item = CauHinhFormDanhGiaMau::query()
            ->where('slug', $slug)
            ->firstOrFail();

        return response()->json([
            'id' => $item->id,
            'ten_form' => $item->ten_form,
            'slug' => $item->slug,
            'cau_hoi' => $item->cau_hoi ?? [],
        ]);
    }

    /**
     * Tạo form đánh giá mẫu mới.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $this->validatePayload($request);
        $validated['slug'] = $this->uniqueSlugFromTenForm($validated['ten_form']);

        $item = CauHinhFormDanhGiaMau::create($validated);

        return response()->json($item, 201);
    }

    /**
     * Cập nhật form đánh giá mẫu.
     */
    public function update(Request $request, CauHinhFormDanhGiaMau $cau_hinh_form_danh_gia_mau): JsonResponse
    {
        $validated = $this->validatePayload($request);
        $validated['slug'] = $this->uniqueSlugFromTenForm(
            $validated['ten_form'],
            $cau_hinh_form_danh_gia_mau->id
        );

        $cau_hinh_form_danh_gia_mau->update($validated);

        return response()->json($cau_hinh_form_danh_gia_mau->fresh());
    }

    /**
     * Xóa form đánh giá mẫu.
     */
    public function destroy(CauHinhFormDanhGiaMau $cau_hinh_form_danh_gia_mau): JsonResponse
    {
        $cau_hinh_form_danh_gia_mau->delete();

        return response()->json(['message' => 'Đã xóa form đánh giá mẫu.']);
    }

    /**
     * @return array<string, mixed>
     */
    private function validatePayload(Request $request): array
    {
        return $request->validate([
            'ten_form' => ['required', 'string', 'max:255'],
            'cau_hoi' => ['nullable', 'array'],
            'cau_hoi.*.cau_hoi' => ['required', 'string', 'max:1000'],
            'cau_hoi.*.loai_danh_gia' => ['required', 'string', Rule::in(['diem', 'van_ban'])],
            'cau_hoi.*.thong_tin_danh_gia' => ['required', 'string', 'max:255'],
            'cau_hoi.*.required' => ['required', 'boolean'],
        ]);
    }

    private function uniqueSlugFromTenForm(string $tenForm, ?int $ignoreId = null): string
    {
        $base = Str::slug($tenForm);
        if ($base === '') {
            $base = 'form-danh-gia';
        }

        $slug = $base;
        $suffix = 2;

        while (
            CauHinhFormDanhGiaMau::query()
                ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
                ->where('slug', $slug)
                ->exists()
        ) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }
}
