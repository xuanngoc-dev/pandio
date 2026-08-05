<?php

namespace App\Http\Controllers\Api;

use App\Models\HeThongThongBao;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class HeThongThongBaoController extends BaseApiController
{
    /**
     * Danh sách thông báo hệ thống — phân trang + tìm kiếm.
     *
     * Query: page, per_page, keyword, loai_thong_bao_id, loai_mau_sac
     */
    public function index(Request $request): JsonResponse
    {
        return $this->handleApi(function () use ($request) {
            $validated = $request->validate([
                'page' => ['sometimes', 'integer', 'min:1'],
                'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
                'keyword' => ['sometimes', 'nullable', 'string', 'max:255'],
                'loai_thong_bao_id' => ['sometimes', 'nullable', 'integer', 'exists:danh_muc_loai_thong_bao,id'],
                'loai_mau_sac' => ['sometimes', 'nullable', 'string', Rule::in(HeThongThongBao::MAU_SAC)],
            ]);

            $perPage = $validated['per_page'] ?? 10;
            $keyword = trim((string) ($validated['keyword'] ?? ''));
            $loaiThongBaoId = $validated['loai_thong_bao_id'] ?? null;
            $loaiMauSac = $validated['loai_mau_sac'] ?? null;

            $query = HeThongThongBao::query()
                ->with([
                    'loaiThongBao:id,ma_loai_thong_bao,ten_loai_thong_bao,icon',
                    'actor:id,name,email',
                ])
                ->when($keyword !== '', function ($q) use ($keyword) {
                    $q->where(function ($inner) use ($keyword) {
                        $inner->where('tieu_de', 'like', "%{$keyword}%")
                            ->orWhere('noi_dung', 'like', "%{$keyword}%");
                    });
                })
                ->when($loaiThongBaoId, fn ($q) => $q->where('loai_thong_bao_id', $loaiThongBaoId))
                ->when($loaiMauSac, fn ($q) => $q->where('loai_mau_sac', $loaiMauSac))
                ->orderByDesc('id');

            $paginator = $query->paginate($perPage);
            $this->appendNguoiNhan($paginator->getCollection());

            return response()->json($paginator);

        }, 'lấy danh sách thông báo');
    }

    /**
     * Thông báo của người đăng nhập:
     * - id nằm trong nguoi_nhan_ids
     * - id không nằm trong nguoi_dung_da_xoa_ids
     *
     * Query: page, per_page
     */
    public function cuaToi(Request $request): JsonResponse
    {
        return $this->handleApi(function () use ($request) {
            $userId = (int) $request->user()->id;

            $validated = $request->validate([
                'page' => ['sometimes', 'integer', 'min:1'],
                'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            ]);

            $perPage = $validated['per_page'] ?? 50;

            $query = $this->queryCuaToi($userId)
                ->with([
                    'loaiThongBao:id,ma_loai_thong_bao,ten_loai_thong_bao,icon',
                    'actor:id,name,email',
                ])
                ->orderByDesc('id');

            $paginator = $query->paginate($perPage);
            $this->appendDaDocChoUser($paginator->getCollection(), $userId);

            return response()->json($paginator);
        }, 'lấy thông báo của tôi');
    }

    /**
     * Đánh dấu một thông báo đã đọc (thêm user vào nguoi_nhan_da_doc_ids).
     */
    public function danhDauDaDoc(Request $request, HeThongThongBao $he_thong_thong_bao): JsonResponse
    {
        return $this->handleApi(function () use ($request, $he_thong_thong_bao) {
            $userId = (int) $request->user()->id;
            $this->assertNguoiNhanChuaXoa($he_thong_thong_bao, $userId);

            $daDocIds = array_map('intval', $he_thong_thong_bao->nguoi_nhan_da_doc_ids ?? []);
            if (! in_array($userId, $daDocIds, true)) {
                $daDocIds[] = $userId;
                $he_thong_thong_bao->update([
                    'nguoi_nhan_da_doc_ids' => array_values($daDocIds),
                ]);
            }

            $item = $he_thong_thong_bao->fresh([
                'loaiThongBao:id,ma_loai_thong_bao,ten_loai_thong_bao,icon',
                'actor:id,name,email',
            ]);
            $this->appendDaDocChoUser(collect([$item]), $userId);

            return response()->json($item);
        }, 'đánh dấu đã đọc thông báo');
    }

    /**
     * Đánh dấu tất cả thông báo của tôi là đã đọc.
     */
    public function danhDauTatCaDaDoc(Request $request): JsonResponse
    {
        return $this->handleApi(function () use ($request) {
            $userId = (int) $request->user()->id;

            $items = $this->queryCuaToi($userId)->get();
            $updated = 0;

            foreach ($items as $item) {
                $daDocIds = array_map('intval', $item->nguoi_nhan_da_doc_ids ?? []);
                if (in_array($userId, $daDocIds, true)) {
                    continue;
                }
                $daDocIds[] = $userId;
                $item->update(['nguoi_nhan_da_doc_ids' => array_values($daDocIds)]);
                $updated++;
            }

            return response()->json([
                'message' => 'Đã đánh dấu tất cả thông báo là đã đọc.',
                'updated' => $updated,
            ]);
        }, 'đánh dấu tất cả đã đọc');
    }

    /**
     * Ẩn thông báo với người dùng hiện tại (thêm vào nguoi_dung_da_xoa_ids).
     */
    public function xoaCuaToi(Request $request, HeThongThongBao $he_thong_thong_bao): JsonResponse
    {
        return $this->handleApi(function () use ($request, $he_thong_thong_bao) {
            $userId = (int) $request->user()->id;
            $this->assertNguoiNhanChuaXoa($he_thong_thong_bao, $userId);

            $daXoaIds = array_map('intval', $he_thong_thong_bao->nguoi_dung_da_xoa_ids ?? []);
            if (! in_array($userId, $daXoaIds, true)) {
                $daXoaIds[] = $userId;
                $he_thong_thong_bao->update([
                    'nguoi_dung_da_xoa_ids' => array_values($daXoaIds),
                ]);
            }

            return response()->json(['message' => 'Đã ẩn thông báo.']);
        }, 'ẩn thông báo của tôi');
    }

    /**
     * Chi tiết một thông báo.
     */
    public function show(HeThongThongBao $he_thong_thong_bao): JsonResponse
    {
        return $this->handleApi(function () use ($he_thong_thong_bao) {
            $he_thong_thong_bao->load([
                'loaiThongBao:id,ma_loai_thong_bao,ten_loai_thong_bao,icon',
                'actor:id,name,email',
            ]);
            $this->appendNguoiNhan(collect([$he_thong_thong_bao]));

            return response()->json($he_thong_thong_bao);

        }, 'lấy chi tiết thông báo');
    }

    /**
     * Tạo thông báo mới.
     */
    public function store(Request $request): JsonResponse
    {
        return $this->handleApi(function () use ($request) {
            $validated = $this->validatePayload($request);

            $item = HeThongThongBao::create($validated);
            $item->load([
                'loaiThongBao:id,ma_loai_thong_bao,ten_loai_thong_bao,icon',
                'actor:id,name,email',
            ]);
            $this->appendNguoiNhan(collect([$item]));

            return response()->json($item, 201);

        }, 'tạo thông báo');
    }

    /**
     * Cập nhật thông báo.
     */
    public function update(Request $request, HeThongThongBao $he_thong_thong_bao): JsonResponse
    {
        return $this->handleApi(function () use ($request, $he_thong_thong_bao) {
            $validated = $this->validatePayload($request);

            $he_thong_thong_bao->update($validated);
            $item = $he_thong_thong_bao->fresh([
                'loaiThongBao:id,ma_loai_thong_bao,ten_loai_thong_bao,icon',
                'actor:id,name,email',
            ]);
            $this->appendNguoiNhan(collect([$item]));

            return response()->json($item);

        }, 'cập nhật thông báo');
    }

    /**
     * Xóa thông báo.
     */
    public function destroy(HeThongThongBao $he_thong_thong_bao): JsonResponse
    {
        return $this->handleApi(function () use ($he_thong_thong_bao) {
            $he_thong_thong_bao->delete();

            return response()->json(['message' => 'Đã xóa thông báo.']);

        }, 'xóa thông báo');
    }

    /**
     * @return array<string, mixed>
     */
    private function validatePayload(Request $request): array
    {
        $validated = $request->validate([
            'nguoi_nhan_ids' => ['required', 'array', 'min:1'],
            'nguoi_nhan_ids.*' => ['integer', 'distinct', 'exists:users,id'],
            'actor_id' => ['nullable', 'integer', 'exists:users,id'],
            'loai_thong_bao_id' => ['required', 'integer', 'exists:danh_muc_loai_thong_bao,id'],
            'loai_mau_sac' => ['required', 'string', Rule::in(HeThongThongBao::MAU_SAC)],
            'tieu_de' => ['required', 'string', 'max:255'],
            'noi_dung' => ['nullable', 'string'],
            'nguoi_nhan_da_doc_ids' => ['sometimes', 'nullable', 'array'],
            'nguoi_nhan_da_doc_ids.*' => ['integer', 'exists:users,id'],
            'nguoi_dung_da_xoa_ids' => ['sometimes', 'nullable', 'array'],
            'nguoi_dung_da_xoa_ids.*' => ['integer', 'exists:users,id'],
            'muc_do_uu_tien' => ['sometimes', 'integer', 'min:1', 'max:5'],
            'du_lieu' => ['sometimes', 'nullable', 'array'],
        ]);

        $validated['nguoi_nhan_ids'] = array_values(array_map('intval', $validated['nguoi_nhan_ids']));
        $validated['muc_do_uu_tien'] = $validated['muc_do_uu_tien'] ?? 1;

        if (! array_key_exists('actor_id', $validated) || $validated['actor_id'] === null) {
            $validated['actor_id'] = $request->user()?->id;
        }

        return $validated;
    }

    /**
     * Query thông báo dành cho user: là người nhận và chưa tự xoá.
     *
     * @return \Illuminate\Database\Eloquent\Builder<HeThongThongBao>
     */
    private function queryCuaToi(int $userId)
    {
        return HeThongThongBao::query()
            ->whereJsonContains('nguoi_nhan_ids', $userId)
            ->where(function ($q) use ($userId) {
                $q->whereNull('nguoi_dung_da_xoa_ids')
                    ->orWhereJsonDoesntContain('nguoi_dung_da_xoa_ids', $userId);
            });
    }

    private function assertNguoiNhanChuaXoa(HeThongThongBao $item, int $userId): void
    {
        $nguoiNhanIds = array_map('intval', $item->nguoi_nhan_ids ?? []);
        if (! in_array($userId, $nguoiNhanIds, true)) {
            abort(403, 'Bạn không phải người nhận thông báo này.');
        }

        $daXoaIds = array_map('intval', $item->nguoi_dung_da_xoa_ids ?? []);
        if (in_array($userId, $daXoaIds, true)) {
            abort(404, 'Thông báo không tồn tại.');
        }
    }

    /**
     * Gắn cờ da_doc cho người dùng hiện tại.
     *
     * @param  \Illuminate\Support\Collection<int, HeThongThongBao>  $items
     */
    private function appendDaDocChoUser($items, int $userId): void
    {
        $items->each(function (HeThongThongBao $item) use ($userId) {
            $daDocIds = array_map('intval', $item->nguoi_nhan_da_doc_ids ?? []);
            $item->setAttribute('da_doc', in_array($userId, $daDocIds, true));
        });
    }

    /**
     * Gắn danh sách người nhận (id + name) vào collection.
     *
     * @param  \Illuminate\Support\Collection<int, HeThongThongBao>  $items
     */
    private function appendNguoiNhan($items): void
    {
        $ids = $items
            ->flatMap(fn (HeThongThongBao $item) => $item->nguoi_nhan_ids ?? [])
            ->unique()
            ->filter()
            ->values()
            ->all();

        if ($ids === []) {
            $items->each(fn (HeThongThongBao $item) => $item->setAttribute('nguoi_nhan', []));

            return;
        }

        $users = User::query()
            ->whereIn('id', $ids)
            ->get(['id', 'name', 'email'])
            ->keyBy('id');

        $items->each(function (HeThongThongBao $item) use ($users) {
            $nguoiNhan = collect($item->nguoi_nhan_ids ?? [])
                ->map(fn ($id) => $users->get((int) $id))
                ->filter()
                ->values()
                ->all();

            $item->setAttribute('nguoi_nhan', $nguoiNhan);
        });
    }
}
