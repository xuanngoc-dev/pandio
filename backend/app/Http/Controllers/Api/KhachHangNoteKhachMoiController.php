<?php

namespace App\Http\Controllers\Api;

use App\Models\KhachHangNoteKhachMoi;
use App\Models\User;
use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;

class KhachHangNoteKhachMoiController extends BaseApiController
{
    /** @var list<array{id: string, ten: string}> */
    private const LOAI_SU_KIEN = [
        ['id' => 'hen_lich', 'ten' => 'Hẹn lịch'],
        ['id' => 'den', 'ten' => 'Đến'],
    ];

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
     * Lịch khách hàng theo khoảng ngày.
     * Mỗi note có thể xuất hiện nhiều lần: Hẹn lịch (ngay_hen_lich), Đến (ngay_den_thuc_te).
     *
     * Query: tu_ngay, den_ngay
     */
    public function lich(Request $request): JsonResponse
    {
        return $this->handleApi(function () use ($request) {
            $validated = $request->validate([
                'tu_ngay' => ['required', 'date'],
                'den_ngay' => ['required', 'date', 'after_or_equal:tu_ngay'],
            ]);

            $tuNgay = Carbon::parse($validated['tu_ngay'])->toDateString();
            $denNgay = Carbon::parse($validated['den_ngay'])->toDateString();

            $rows = KhachHangNoteKhachMoi::query()
                ->with(['nguoiTaoUser:id,name,phone'])
                ->where(function ($q) use ($tuNgay, $denNgay) {
                    $q->where(function ($inner) use ($tuNgay, $denNgay) {
                        $inner->whereNotNull('ngay_hen_lich')
                            ->whereDate('ngay_hen_lich', '>=', $tuNgay)
                            ->whereDate('ngay_hen_lich', '<=', $denNgay);
                    })->orWhere(function ($inner) use ($tuNgay, $denNgay) {
                        $inner->whereNotNull('ngay_den_thuc_te')
                            ->whereDate('ngay_den_thuc_te', '>=', $tuNgay)
                            ->whereDate('ngay_den_thuc_te', '<=', $denNgay);
                    });
                })
                ->orderBy('id')
                ->get();

            $this->appendSaleUsersToCollection($rows);

            return response()->json([
                'loai_su_kien' => self::LOAI_SU_KIEN,
                'items' => $this->expandLichItems($rows, $tuNgay, $denNgay),
            ]);

        }, 'lấy lịch khách hàng');
    }

    /**
     * Chi tiết lịch khách hàng theo ngày (+ loại sự kiện).
     *
     * Query: ngay (required), loai (optional: hen_lich|den), trang_thai (optional)
     */
    public function lichChiTiet(Request $request): JsonResponse
    {
        return $this->handleApi(function () use ($request) {
            $validated = $request->validate([
                'ngay' => ['required', 'date'],
                'loai' => ['sometimes', 'nullable', Rule::in(['hen_lich', 'den'])],
                'trang_thai' => ['sometimes', 'nullable', Rule::in([
                    'cho_hen',
                    'da_den',
                    'khong_den',
                    'da_ky_hd',
                    'da_huy',
                ])],
            ]);

            $ngay = Carbon::parse($validated['ngay'])->toDateString();
            $loai = $validated['loai'] ?? null;
            $trangThai = $validated['trang_thai'] ?? null;

            $rows = KhachHangNoteKhachMoi::query()
                ->with(['nguoiTaoUser:id,name,phone'])
                ->where(function ($q) use ($ngay, $loai) {
                    if ($loai === 'hen_lich') {
                        $q->whereDate('ngay_hen_lich', $ngay);

                        return;
                    }
                    if ($loai === 'den') {
                        $q->whereDate('ngay_den_thuc_te', $ngay);

                        return;
                    }

                    $q->whereDate('ngay_hen_lich', $ngay)
                        ->orWhereDate('ngay_den_thuc_te', $ngay);
                })
                ->orderBy('id')
                ->get();

            $this->appendSaleUsersToCollection($rows);

            $items = $this->expandLichItems($rows, $ngay, $ngay);
            if ($loai) {
                $items = array_values(array_filter(
                    $items,
                    fn (array $item) => $item['loai'] === $loai,
                ));
            }
            if ($trangThai) {
                $items = array_values(array_filter(
                    $items,
                    fn (array $item) => ($item['trang_thai'] ?? '') === $trangThai,
                ));
                $items = $this->uniqueLichItemsById($items);
            }

            return response()->json([
                'ngay' => $ngay,
                'items' => $items,
            ]);

        }, 'lấy chi tiết lịch khách hàng');
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

    /**
     * @param  Collection<int, KhachHangNoteKhachMoi>  $rows
     * @return list<array<string, mixed>>
     */
    private function expandLichItems(Collection $rows, string $tuNgay, string $denNgay): array
    {
        $items = [];
        foreach ($rows as $row) {
            $hen = $this->toDateString($row->ngay_hen_lich);
            $den = $this->toDateString($row->ngay_den_thuc_te);

            if ($hen && $hen >= $tuNgay && $hen <= $denNgay) {
                $items[] = $this->mapLichItem($row, 'hen_lich', $hen);
            }
            if ($den && $den >= $tuNgay && $den <= $denNgay) {
                $items[] = $this->mapLichItem($row, 'den', $den);
            }
        }

        usort($items, function (array $a, array $b) {
            $dateCmp = strcmp((string) $a['ngay'], (string) $b['ngay']);
            if ($dateCmp !== 0) {
                return $dateCmp;
            }

            $loaiRank = ['hen_lich' => 0, 'den' => 1];
            $loaiCmp = ($loaiRank[$a['loai']] ?? 9) <=> ($loaiRank[$b['loai']] ?? 9);
            if ($loaiCmp !== 0) {
                return $loaiCmp;
            }

            $nameCmp = strcmp(
                mb_strtolower((string) ($a['ten_khach'] ?? '')),
                mb_strtolower((string) ($b['ten_khach'] ?? '')),
            );
            if ($nameCmp !== 0) {
                return $nameCmp;
            }

            return ($a['id'] <=> $b['id']);
        });

        return array_values($items);
    }

    /**
     * @param  list<array<string, mixed>>  $items
     * @return list<array<string, mixed>>
     */
    private function uniqueLichItemsById(array $items): array
    {
        $seen = [];
        $unique = [];
        foreach ($items as $item) {
            $id = $item['id'] ?? null;
            if ($id === null || isset($seen[$id])) {
                continue;
            }
            $seen[$id] = true;
            $unique[] = $item;
        }

        return $unique;
    }

    /**
     * @return array<string, mixed>
     */
    private function mapLichItem(KhachHangNoteKhachMoi $row, string $loai, string $ngay): array
    {
        $nguoiTao = $row->nguoiTaoUser;

        return [
            'id' => $row->id,
            'event_key' => $loai.'-'.$row->id,
            'loai' => $loai,
            'ngay' => $ngay,
            'ten_khach' => $row->ten_khach,
            'sdt' => $row->sdt,
            'trang_thai' => $row->trang_thai,
            'ghi_chu' => $row->ghi_chu,
            'nguon_khach' => $row->nguon_khach,
            'tra_cuu_hd' => $row->tra_cuu_hd,
            'hinh_thuc_dat_coc' => $row->hinh_thuc_dat_coc,
            'ngay_hen_lich' => $this->toDateString($row->ngay_hen_lich),
            'ngay_den_thuc_te' => $this->toDateString($row->ngay_den_thuc_te),
            'phu_trach_sale_users' => $row->getAttribute('phu_trach_sale_users') ?? [],
            'nguoi_tao_user' => $nguoiTao
                ? [
                    'id' => $nguoiTao->id,
                    'name' => $nguoiTao->name,
                    'phone' => $nguoiTao->phone,
                ]
                : null,
        ];
    }

    private function toDateString(mixed $value): ?string
    {
        if ($value instanceof DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        $raw = substr((string) $value, 0, 10);

        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $raw) === 1 ? $raw : null;
    }

    /**
     * @param  Collection<int, KhachHangNoteKhachMoi>  $rows
     */
    private function appendSaleUsersToCollection(Collection $rows): void
    {
        $allIds = $rows
            ->flatMap(fn (KhachHangNoteKhachMoi $item) => $item->phu_trach_sale ?? [])
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $usersById = $this->usersByIds($allIds);

        foreach ($rows as $item) {
            $ids = collect($item->phu_trach_sale ?? [])->filter()->values();
            $item->setAttribute('phu_trach_sale_users', $this->mapUsersByIds($ids, $usersById));
        }
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
        return $this->mapUsersByIds($ids, $this->usersByIds(
            $ids->map(fn ($id) => (int) $id)->unique()->values()
        ));
    }

    /**
     * @param  Collection<int, int>  $ids
     * @return Collection<int, User>
     */
    private function usersByIds(Collection $ids): Collection
    {
        if ($ids->isEmpty()) {
            return collect();
        }

        return User::query()
            ->whereIn('id', $ids->all())
            ->get(['id', 'name', 'phone'])
            ->keyBy('id');
    }

    /**
     * @param  Collection<int, mixed>  $ids
     * @param  Collection<int, User>  $users
     * @return list<array{id: int, name: string|null, phone: string|null}>
     */
    private function mapUsersByIds(Collection $ids, Collection $users): array
    {
        if ($ids->isEmpty()) {
            return [];
        }

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
