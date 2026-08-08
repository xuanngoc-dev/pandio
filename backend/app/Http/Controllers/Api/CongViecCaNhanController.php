<?php

namespace App\Http\Controllers\Api;

use App\Models\CongViecCaNhan;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CongViecCaNhanController extends BaseApiController
{
    /**
     * Danh sách công việc cá nhân — phân trang + tìm kiếm.
     * Chỉ trả về việc do user hiện tại giao hoặc được giao phụ trách.
     *
     * Query: page, per_page, keyword, trang_thai, muc_do_uu_tien
     */
    public function index(Request $request): JsonResponse
    {
        return $this->handleApi(function () use ($request) {
            $validated = $request->validate([
                'page' => ['sometimes', 'integer', 'min:1'],
                'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
                'keyword' => ['sometimes', 'nullable', 'string', 'max:255'],
                'trang_thai' => ['sometimes', 'nullable', 'string', Rule::in(CongViecCaNhan::TRANG_THAI)],
                'muc_do_uu_tien' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:5'],
            ]);

            $perPage = $validated['per_page'] ?? 10;
            $keyword = trim((string) ($validated['keyword'] ?? ''));
            $trangThai = $validated['trang_thai'] ?? null;
            $mucDoUuTien = $validated['muc_do_uu_tien'] ?? null;
            $userId = (int) ($request->user()?->id ?? 0);

            $query = CongViecCaNhan::query()
                ->with(['nguoiGiaoViec:id,name,email'])
                ->when($userId > 0, function ($q) use ($userId) {
                    // Chỉ việc mình giao hoặc mình nằm trong danh sách phụ trách
                    $q->where(function ($inner) use ($userId) {
                        $inner->where('nguoi_giao_viec_id', $userId)
                            ->orWhereJsonContains('nguoi_phu_trach_viec_ids', $userId);
                    });
                }, function ($q) {
                    $q->whereRaw('1 = 0');
                })
                ->when($keyword !== '', function ($q) use ($keyword) {
                    $q->where(function ($inner) use ($keyword) {
                        $inner->where('tieu_de', 'like', "%{$keyword}%")
                            ->orWhere('mo_ta', 'like', "%{$keyword}%")
                            ->orWhere('ghi_chu', 'like', "%{$keyword}%")
                            ->orWhere('lien_ket', 'like', "%{$keyword}%");
                    });
                })
                ->when($trangThai, fn ($q) => $q->where('trang_thai', $trangThai))
                ->when($mucDoUuTien, fn ($q) => $q->where('muc_do_uu_tien', $mucDoUuTien))
                ->orderByDesc('id');

            $paginator = $query->paginate($perPage);
            $this->appendNguoiPhuTrach($paginator->getCollection());

            return response()->json($paginator);

        }, 'lấy danh sách công việc cá nhân');
    }

    /**
     * Chi tiết một công việc cá nhân.
     */
    public function show(CongViecCaNhan $cong_viec_ca_nhan): JsonResponse
    {
        return $this->handleApi(function () use ($cong_viec_ca_nhan) {
            $cong_viec_ca_nhan->load(['nguoiGiaoViec:id,name,email']);
            $this->appendNguoiPhuTrach(collect([$cong_viec_ca_nhan]));

            return response()->json($cong_viec_ca_nhan);

        }, 'lấy chi tiết công việc cá nhân');
    }

    /**
     * Tạo công việc cá nhân mới.
     */
    public function store(Request $request): JsonResponse
    {
        return $this->handleApi(function () use ($request) {
            $validated = $this->validatePayload($request, isCreate: true);

            $item = CongViecCaNhan::create($validated);
            $item->load(['nguoiGiaoViec:id,name,email']);
            $this->appendNguoiPhuTrach(collect([$item]));

            return response()->json($item, 201);

        }, 'tạo công việc cá nhân');
    }

    /**
     * Cập nhật công việc cá nhân.
     */
    public function update(Request $request, CongViecCaNhan $cong_viec_ca_nhan): JsonResponse
    {
        return $this->handleApi(function () use ($request, $cong_viec_ca_nhan) {
            $validated = $this->validatePayload($request, isCreate: false);
            // Giữ người giao việc gốc khi cập nhật (không cho client đổi).
            unset($validated['nguoi_giao_viec_id']);

            $cong_viec_ca_nhan->update($validated);
            $item = $cong_viec_ca_nhan->fresh(['nguoiGiaoViec:id,name,email']);
            $this->appendNguoiPhuTrach(collect([$item]));

            return response()->json($item);

        }, 'cập nhật công việc cá nhân');
    }

    /**
     * Xóa công việc cá nhân.
     */
    public function destroy(CongViecCaNhan $cong_viec_ca_nhan): JsonResponse
    {
        return $this->handleApi(function () use ($cong_viec_ca_nhan) {
            $cong_viec_ca_nhan->delete();

            return response()->json(['message' => 'Đã xóa công việc cá nhân.']);

        }, 'xóa công việc cá nhân');
    }

    /**
     * @return array<string, mixed>
     */
    private function validatePayload(Request $request, bool $isCreate = true): array
    {
        $user = $request->user();
        $isAdmin = ($user?->role ?? null) === 'admin';

        $validated = $request->validate([
            'nguoi_phu_trach_viec_ids' => [$isAdmin ? 'required' : 'sometimes', 'array', 'min:1'],
            'nguoi_phu_trach_viec_ids.*' => ['integer', 'distinct', 'exists:users,id'],
            'tieu_de' => ['required', 'string', 'max:255'],
            'mo_ta' => ['nullable', 'string'],
            'ghi_chu' => ['nullable', 'string'],
            'lien_ket' => ['nullable', 'string', 'max:500'],
            'thoi_gian_thuc_hien' => ['nullable', 'array'],
            'thoi_gian_thuc_hien.bat_dau' => ['required_with:thoi_gian_thuc_hien', 'date_format:Y-m-d'],
            'thoi_gian_thuc_hien.ket_thuc' => [
                'required_with:thoi_gian_thuc_hien',
                'date_format:Y-m-d',
                'after_or_equal:thoi_gian_thuc_hien.bat_dau',
            ],
            'muc_do_uu_tien' => ['required', 'integer', 'min:1', 'max:5'],
            'trang_thai' => ['sometimes', 'string', Rule::in(CongViecCaNhan::TRANG_THAI)],
        ]);

        if ($isCreate) {
            // Người giao việc luôn là user hiện tại khi tạo mới
            $validated['nguoi_giao_viec_id'] = $user?->id;
        }

        if ($isAdmin) {
            $validated['nguoi_phu_trach_viec_ids'] = array_values(
                array_map('intval', $validated['nguoi_phu_trach_viec_ids'] ?? [])
            );
        } elseif ($isCreate) {
            // User thường tạo mới: người phụ trách luôn là chính mình
            $validated['nguoi_phu_trach_viec_ids'] = $user?->id ? [(int) $user->id] : [];
        } else {
            // User thường cập nhật: không cho đổi người phụ trách
            unset($validated['nguoi_phu_trach_viec_ids']);
        }

        if ($isCreate) {
            $validated['trang_thai'] = 'chua_hoan_thanh';
        } else {
            $validated['trang_thai'] = $validated['trang_thai'] ?? 'chua_hoan_thanh';
        }

        if (array_key_exists('thoi_gian_thuc_hien', $validated) && $validated['thoi_gian_thuc_hien'] === null) {
            $validated['thoi_gian_thuc_hien'] = null;
        }

        if (array_key_exists('lien_ket', $validated)) {
            $lienKet = trim((string) ($validated['lien_ket'] ?? ''));
            $validated['lien_ket'] = $lienKet !== '' ? $lienKet : null;
        }

        return $validated;
    }

    /**
     * Gắn danh sách người phụ trách (id + name) vào collection.
     *
     * @param  \Illuminate\Support\Collection<int, CongViecCaNhan>  $items
     */
    private function appendNguoiPhuTrach($items): void
    {
        $ids = $items
            ->flatMap(fn (CongViecCaNhan $item) => $item->nguoi_phu_trach_viec_ids ?? [])
            ->unique()
            ->filter()
            ->values()
            ->all();

        if ($ids === []) {
            $items->each(fn (CongViecCaNhan $item) => $item->setAttribute('nguoi_phu_trach', []));

            return;
        }

        $users = User::query()
            ->whereIn('id', $ids)
            ->get(['id', 'name', 'email'])
            ->keyBy('id');

        $items->each(function (CongViecCaNhan $item) use ($users) {
            $nguoiPhuTrach = collect($item->nguoi_phu_trach_viec_ids ?? [])
                ->map(fn ($id) => $users->get((int) $id))
                ->filter()
                ->values()
                ->all();

            $item->setAttribute('nguoi_phu_trach', $nguoiPhuTrach);
        });
    }
}
