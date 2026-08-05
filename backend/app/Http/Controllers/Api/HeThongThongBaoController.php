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
