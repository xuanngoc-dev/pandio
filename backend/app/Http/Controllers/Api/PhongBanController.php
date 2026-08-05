<?php

namespace App\Http\Controllers\Api;

use App\Models\NhanVien;
use App\Models\PhongBan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PhongBanController extends BaseApiController
{
    /**
     * Danh sách phòng ban — phân trang + tìm kiếm.
     *
     * Query: page, per_page, keyword
     */
    public function index(Request $request): JsonResponse
    {
        return $this->handleApi(function () use ($request) {
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

            $paginator = $query->paginate($perPage);
            $ids = $paginator->getCollection()->pluck('id')->map(fn ($id) => (int) $id)->all();
            $countMap = $this->countNhanVienByPhongBanIds($ids);

            $paginator->getCollection()->transform(function (PhongBan $phongBan) use ($countMap) {
                $phongBan->setAttribute('so_luong_nhan_vien', $countMap[(int) $phongBan->id] ?? 0);

                return $phongBan;
            });

            return response()->json($paginator);

        }, 'lấy danh sách phòng ban');
    }

    /**
     * Chi tiết một phòng ban.
     */
    public function show(PhongBan $phong_ban): JsonResponse
    {
        return $this->handleApi(function () use ($phong_ban) {
            $phong_ban->setAttribute('so_luong_nhan_vien', $phong_ban->countNhanVien());

            return response()->json($phong_ban);

        }, 'lấy chi tiết phòng ban');
    }

    /**
     * Tạo phòng ban mới.
     */
    public function store(Request $request): JsonResponse
    {
        return $this->handleApi(function () use ($request) {
            $validated = $request->validate([
                'ten_phong_ban' => ['required', 'string', 'max:255'],
                'ma_phong_ban' => ['required', 'string', 'max:50', 'unique:phong_ban,ma_phong_ban'],
                'truong_phong' => ['nullable', 'string', 'max:255'],
                'mo_ta' => ['nullable', 'string'],
                'ghi_chu' => ['nullable', 'string'],
            ]);

            $phongBan = PhongBan::create($validated);
            $phongBan->setAttribute('so_luong_nhan_vien', 0);

            return response()->json($phongBan, 201);

        }, 'tạo phòng ban');
    }

    /**
     * Cập nhật phòng ban.
     */
    public function update(Request $request, PhongBan $phong_ban): JsonResponse
    {
        return $this->handleApi(function () use ($request, $phong_ban) {
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
            $fresh = $phong_ban->fresh();
            $fresh->setAttribute('so_luong_nhan_vien', $fresh->countNhanVien());

            return response()->json($fresh);

        }, 'cập nhật phòng ban');
    }

    /**
     * Xóa phòng ban — chỉ khi không còn nhân viên.
     */
    public function destroy(PhongBan $phong_ban): JsonResponse
    {
        return $this->handleApi(function () use ($phong_ban) {
            $count = $phong_ban->countNhanVien();
            if ($count > 0) {
                return response()->json([
                    'message' => "Không thể xóa phòng ban đang có {$count} nhân viên. Vui lòng chuyển hoặc xóa nhân viên khỏi phòng ban trước.",
                ], 422);
            }

            $phong_ban->delete();

            return response()->json(['message' => 'Đã xóa phòng ban.']);

        }, 'xóa phòng ban');
    }

    /**
     * Danh sách nhân viên thuộc phòng ban.
     *
     * Query: page, per_page, keyword
     */
    public function nhanVien(Request $request, PhongBan $phong_ban): JsonResponse
    {
        return $this->handleApi(function () use ($request, $phong_ban) {
            $validated = $request->validate([
                'page' => ['sometimes', 'integer', 'min:1'],
                'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
                'keyword' => ['sometimes', 'nullable', 'string', 'max:255'],
            ]);

            $perPage = $validated['per_page'] ?? 10;
            $keyword = trim((string) ($validated['keyword'] ?? ''));

            $query = $phong_ban->nhanViensQuery()
                ->with(['user:id,name,email,phone,status'])
                ->when($keyword !== '', function ($q) use ($keyword) {
                    $q->whereHas('user', function ($userQuery) use ($keyword) {
                        $userQuery->where(function ($inner) use ($keyword) {
                            $inner->where('name', 'like', "%{$keyword}%")
                                ->orWhere('email', 'like', "%{$keyword}%")
                                ->orWhere('phone', 'like', "%{$keyword}%");
                        });
                    });
                })
                ->orderByDesc('id');

            return response()->json($query->paginate($perPage));

        }, 'lấy danh sách nhân viên phòng ban');
    }

    /**
     * Xóa nhân viên khỏi phòng ban (gỡ id khỏi phong_ban_ids).
     */
    public function removeNhanVien(PhongBan $phong_ban, NhanVien $nhan_vien): JsonResponse
    {
        return $this->handleApi(function () use ($phong_ban, $nhan_vien) {
            $ids = $nhan_vien->phong_ban_ids ?? [];
            if (! is_array($ids)) {
                $ids = [];
            }

            $phongBanId = (int) $phong_ban->id;
            $normalized = array_values(array_unique(array_map('intval', $ids)));

            if (! in_array($phongBanId, $normalized, true)) {
                return response()->json([
                    'message' => 'Nhân viên không thuộc phòng ban này.',
                ], 422);
            }

            $remaining = array_values(array_filter(
                $normalized,
                fn (int $id) => $id !== $phongBanId,
            ));

            $nhan_vien->phong_ban_ids = $remaining !== [] ? $remaining : null;
            $nhan_vien->save();

            return response()->json([
                'message' => 'Đã xóa nhân viên khỏi phòng ban.',
                'nhan_vien' => $nhan_vien->fresh()->load(['user:id,name,email,phone,status']),
            ]);

        }, 'xóa nhân viên khỏi phòng ban');
    }

    /**
     * @param  list<int>  $ids
     * @return array<int, int>
     */
    private function countNhanVienByPhongBanIds(array $ids): array
    {
        $countMap = [];
        foreach ($ids as $id) {
            $countMap[$id] = 0;
        }

        if ($ids === []) {
            return $countMap;
        }

        $nhanViens = NhanVien::query()
            ->where(function ($q) use ($ids) {
                foreach ($ids as $id) {
                    $q->orWhereJsonContains('phong_ban_ids', $id);
                }
            })
            ->get(['id', 'phong_ban_ids']);

        foreach ($nhanViens as $nhanVien) {
            foreach ($nhanVien->phong_ban_ids ?? [] as $pbId) {
                $pbId = (int) $pbId;
                if (array_key_exists($pbId, $countMap)) {
                    $countMap[$pbId]++;
                }
            }
        }

        return $countMap;
    }
}
