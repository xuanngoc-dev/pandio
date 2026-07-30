<?php

namespace App\Http\Controllers\Api;

use App\Models\KhachHangNoteKhachMoi;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;

class KhachHangNoteKhachMoiController extends BaseApiController
{
    /**
     * Danh sách note khách mới — phân trang + tìm kiếm / lọc.
     *
     * Query: page, per_page, keyword, trang_thai, ngay_hen_tu, ngay_hen_den
     */
    public function index(Request $request): JsonResponse
    {
        return $this->handleApi(function () use ($request) {
            $validated = $request->validate([
                'page' => ['sometimes', 'integer', 'min:1'],
                'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
                'keyword' => ['sometimes', 'nullable', 'string', 'max:255'],
                'trang_thai' => ['sometimes', 'nullable', 'string', 'max:50'],
                'ngay_hen_tu' => ['sometimes', 'nullable', 'date'],
                'ngay_hen_den' => ['sometimes', 'nullable', 'date'],
            ]);

            $perPage = $validated['per_page'] ?? 10;
            $keyword = trim((string) ($validated['keyword'] ?? ''));
            $trangThai = $validated['trang_thai'] ?? null;

            $query = KhachHangNoteKhachMoi::query()
                ->with(['nguoiTaoUser:id,name,phone'])
                ->when($keyword !== '', function ($q) use ($keyword) {
                    $q->where(function ($inner) use ($keyword) {
                        $inner->where('ten_khach', 'like', "%{$keyword}%")
                            ->orWhere('sdt', 'like', "%{$keyword}%")
                            ->orWhere('ghi_chu', 'like', "%{$keyword}%")
                            ->orWhere('tra_cuu_hd', 'like', "%{$keyword}%")
                            ->orWhere('nguon_khach', 'like', "%{$keyword}%");
                    });
                })
                ->when($trangThai, fn ($q) => $q->where('trang_thai', $trangThai))
                ->when(! empty($validated['ngay_hen_tu']), function ($q) use ($validated) {
                    $q->whereDate('ngay_hen_lich', '>=', $validated['ngay_hen_tu']);
                })
                ->when(! empty($validated['ngay_hen_den']), function ($q) use ($validated) {
                    $q->whereDate('ngay_hen_lich', '<=', $validated['ngay_hen_den']);
                })
                ->orderByDesc('ngay_hen_lich')
                ->orderByDesc('id');

            $paginator = $query->paginate($perPage);
            $paginator->getCollection()->transform(fn ($item) => $this->appendSaleUsers($item));

            return response()->json($paginator);

        }, 'lấy danh sách note khách mới');
    }

    /**
     * Chi tiết một note khách mới.
     */
    public function show(KhachHangNoteKhachMoi $khach_hang_note_khach_moi): JsonResponse
    {
        return $this->handleApi(function () use ($khach_hang_note_khach_moi) {
            $khach_hang_note_khach_moi->load(['nguoiTaoUser:id,name,phone']);

            return response()->json($this->appendSaleUsers($khach_hang_note_khach_moi));

        }, 'lấy chi tiết note khách mới');
    }

    /**
     * Tạo note khách mới.
     */
    public function store(Request $request): JsonResponse
    {
        return $this->handleApi(function () use ($request) {
            $validated = $this->validatePayload($request);
            $validated['nguoi_tao'] = $request->user()->id;

            $item = KhachHangNoteKhachMoi::create($validated);
            $item->load(['nguoiTaoUser:id,name,phone']);

            return response()->json($this->appendSaleUsers($item), 201);

        }, 'tạo note khách mới');
    }

    /**
     * Cập nhật note khách mới.
     */
    public function update(Request $request, KhachHangNoteKhachMoi $khach_hang_note_khach_moi): JsonResponse
    {
        return $this->handleApi(function () use ($request, $khach_hang_note_khach_moi) {
            $validated = $this->validatePayload($request);
            unset($validated['nguoi_tao']);

            $khach_hang_note_khach_moi->update($validated);

            return response()->json(
                $this->appendSaleUsers($khach_hang_note_khach_moi->fresh()->load(['nguoiTaoUser:id,name,phone']))
            );

        }, 'cập nhật note khách mới');
    }

    /**
     * Xóa note khách mới.
     */
    public function destroy(KhachHangNoteKhachMoi $khach_hang_note_khach_moi): JsonResponse
    {
        return $this->handleApi(function () use ($khach_hang_note_khach_moi) {
            $khach_hang_note_khach_moi->delete();

            return response()->json(['message' => 'Đã xóa note khách mới.']);

        }, 'xóa note khách mới');
    }

    /**
     * @return array<string, mixed>
     */
    private function validatePayload(Request $request): array
    {
        $validated = $request->validate([
            'ten_khach' => ['required', 'string', 'max:255'],
            'sdt' => ['nullable', 'string', 'max:20'],
            'ngay_hen_lich' => ['nullable', 'date'],
            'phu_trach_sale' => ['nullable', 'array'],
            'phu_trach_sale.*' => ['integer', 'exists:users,id'],
            'ghi_chu' => ['nullable', 'string'],
            'nguon_khach' => ['nullable', 'string', 'max:100'],
            'ngay_den_thuc_te' => ['nullable', 'date'],
            'trang_thai' => ['required', 'string', 'max:50', Rule::in([
                'cho_hen',
                'da_den',
                'khong_den',
                'da_ky_hd',
                'da_huy',
            ])],
            'tra_cuu_hd' => ['nullable', 'string', 'max:255'],
            'hinh_thuc_dat_coc' => ['nullable', 'string', 'max:50', Rule::in([
                'tien_mat',
                'chuyen_khoan',
                'khong_coc',
                'khac',
            ])],
            'nguoi_tao' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        $validated['phu_trach_sale'] = collect($validated['phu_trach_sale'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        return $validated;
    }

    private function appendSaleUsers(KhachHangNoteKhachMoi $item): KhachHangNoteKhachMoi
    {
        $ids = collect($item->phu_trach_sale ?? [])->filter()->values();
        $item->setAttribute('phu_trach_sale_users', $this->resolveUsers($ids));

        return $item;
    }

    /**
     * @param  Collection<int, mixed>  $ids
     * @return list<array{id: int, name: string|null, phone: string|null}>
     */
    private function resolveUsers(Collection $ids): array
    {
        if ($ids->isEmpty()) {
            return [];
        }

        $users = User::query()
            ->whereIn('id', $ids->all())
            ->get(['id', 'name', 'phone'])
            ->keyBy('id');

        return $ids
            ->map(function ($id) use ($users) {
                $user = $users->get((int) $id);
                if (! $user) {
                    return null;
                }

                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'phone' => $user->phone,
                ];
            })
            ->filter()
            ->values()
            ->all();
    }
}
