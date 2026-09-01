<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Support\Media;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UserController extends BaseApiController
{
    /**
     * Danh sách user (nhân sự) có phân trang + tìm kiếm.
     *
     * Query: page, per_page, keyword, status
     */
    public function index(Request $request): JsonResponse
    {
        return $this->handleApi(function () use ($request) {
            $validated = $request->validate([
                'page' => ['sometimes', 'integer', 'min:1'],
                'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
                'keyword' => ['sometimes', 'nullable', 'string', 'max:255'],
                'status' => ['sometimes', 'nullable', 'string', 'max:50'],
            ]);

            $perPage = $validated['per_page'] ?? 10;
            $keyword = trim((string) ($validated['keyword'] ?? ''));
            $status = $validated['status'] ?? null;

            $query = User::query()
                ->with(['nhanVien.vaiTro'])
                ->select(['id', 'name', 'email', 'phone', 'role', 'status', 'created_at', 'updated_at'])
                ->when($keyword !== '', function ($q) use ($keyword) {
                    $q->where(function ($inner) use ($keyword) {
                        $inner->where('name', 'like', "%{$keyword}%")
                            ->orWhere('email', 'like', "%{$keyword}%")
                            ->orWhere('phone', 'like', "%{$keyword}%");
                    });
                })
                ->when($status, fn ($q) => $q->where('status', $status))
                ->orderByDesc('id');

            $paginator = $query->paginate($perPage);

            return response()->json($paginator);

        }, 'lấy danh sách nhân sự');
    }

    /**
     * Chi tiết một nhân sự (kèm nhan_vien).
     */
    public function show(User $user): JsonResponse
    {
        return $this->handleApi(function () use ($user) {
            $user->load(['nhanVien.vaiTro']);

            return response()->json($user);

        }, 'lấy chi tiết nhân sự');
    }

    /**
     * Upload hình ảnh nhân viên vào storage/app/public/nhan-vien.
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

            $path = $validated['hinh_anh']->store('nhan-vien', 'public');

            return response()->json([
                'path' => $path,
                'url' => Media::url($path),
            ], 201);

        }, 'upload hình ảnh nhân sự');
    }

    /**
     * Tạo nhân sự: users + nhan_vien.
     */
    public function store(Request $request): JsonResponse
    {
        return $this->handleApi(function () use ($request) {
            $validated = $this->validatePayload($request);

            $user = DB::transaction(function () use ($validated) {
                $user = User::create([
                    'name' => $validated['name'],
                    'email' => $validated['email'],
                    'phone' => $validated['phone'],
                    'password' => $validated['password'],
                    'role' => $validated['role'],
                    'status' => $validated['status'],
                ]);

                $user->nhanVien()->create($this->nhanVienAttributes($validated));

                return $user->load(['nhanVien.vaiTro']);
            });

            return response()->json($user, 201);

        }, 'tạo nhân sự');
    }

    /**
     * Cập nhật nhân sự: users + nhan_vien.
     */
    public function update(Request $request, User $user): JsonResponse
    {
        return $this->handleApi(function () use ($request, $user) {
            $validated = $this->validatePayload($request, $user);
            $oldHinhAnh = $user->nhanVien?->getRawOriginal('hinh_anh');

            $user = DB::transaction(function () use ($user, $validated) {
                $userData = [
                    'name' => $validated['name'],
                    'email' => $validated['email'],
                    'phone' => $validated['phone'],
                    'status' => $validated['status'],
                ];

                // Chỉ cập nhật role khi client gửi lên (VD: bật Điều phối → coordinator)
                if (array_key_exists('role', $validated)) {
                    $userData['role'] = $validated['role'];
                }

                if (! empty($validated['password'])) {
                    $userData['password'] = $validated['password'];
                }

                $user->update($userData);

                $nhanVienData = $this->nhanVienAttributes($validated);

                if ($user->nhanVien) {
                    $user->nhanVien->update($nhanVienData);
                } else {
                    $user->nhanVien()->create($nhanVienData);
                }

                return $user->fresh()->load(['nhanVien.vaiTro']);
            });

            $newHinhAnh = $user->nhanVien?->getRawOriginal('hinh_anh');
            if ($oldHinhAnh && $oldHinhAnh !== $newHinhAnh) {
                $this->deleteHinhAnhFile($oldHinhAnh);
            }

            return response()->json($user);

        }, 'cập nhật nhân sự');
    }

    /**
     * Xóa nhân sự (cascade xóa nhan_vien + file ảnh).
     */
    public function destroy(User $user): JsonResponse
    {
        return $this->handleApi(function () use ($user) {
            $hinhAnh = $user->nhanVien?->getRawOriginal('hinh_anh');
            $user->delete();
            $this->deleteHinhAnhFile($hinhAnh);

            return response()->json(['message' => 'Đã xóa nhân sự.']);

        }, 'xóa nhân sự');
    }

    /**
     * Xóa file ảnh trên disk public (nếu thuộc thư mục nhan-vien).
     */
    private function deleteHinhAnhFile(?string $path): void
    {
        $normalized = Media::normalizePath($path);
        if (! $normalized || ! str_starts_with($normalized, 'nhan-vien/')) {
            return;
        }

        Media::delete($normalized);
    }

    /**
     * Validate payload tạo/cập nhật.
     *
     * @return array<string, mixed>
     */
    private function validatePayload(Request $request, ?User $user = null): array
    {
        $isUpdate = $user !== null;

        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user?->id),
            ],
            'phone' => [
                'required',
                'string',
                'max:20',
                'regex:/^(0|\+84)(3|5|7|8|9)[0-9]{8}$/',
                Rule::unique('users', 'phone')->ignore($user?->id),
            ],
            'password' => [
                $isUpdate ? 'nullable' : 'required',
                'string',
                Password::defaults(),
            ],
            'role' => [
                $isUpdate ? 'sometimes' : 'required',
                'string',
                Rule::in(['user', 'admin', 'coordinator']),
            ],
            'status' => ['required', 'string', Rule::in(['active', 'inactive'])],

            'hinh_anh' => ['nullable', 'string', 'max:1000'],
            'phong_ban_ids' => ['nullable', 'array'],
            'phong_ban_ids.*' => ['integer', 'distinct', 'exists:phong_ban,id'],
            'vai_tro_id' => ['nullable', 'integer', 'exists:vai_tro,id'],
            'ngan_hang' => ['nullable', 'string', 'max:255'],
            'chi_nhanh' => ['nullable', 'string', 'max:255'],
            'so_tai_khoan' => ['nullable', 'string', 'max:255'],
            'chu_tai_khoan' => ['nullable', 'string', 'max:255'],
            'gioi_tinh' => ['nullable', 'string', 'max:20'],
            'ngay_sinh' => ['nullable', 'date'],
            'cccd' => [
                'nullable',
                'string',
                'max:20',
                Rule::unique('nhan_vien', 'cccd')->ignore($user?->nhanVien?->id),
            ],
            'vi_tri_lam_viec' => ['nullable', 'string', 'max:255'],
            'ngay_vao_cong_ty' => ['nullable', 'date'],
            'ngay_ky_hop_dong' => ['nullable', 'date'],
            'loai_nhan_vien' => ['nullable', Rule::in(['part_time', 'full_time'])],
            'loai_hop_dong' => ['nullable', Rule::in(['chinh_thuc', 'hoc_viec', 'thu_viec'])],

            'cong_chuan' => ['nullable', 'numeric', 'min:0'],
            'tham_gia_bao_hiem' => ['sometimes', 'boolean'],
            'so_nguoi_phu_thuoc' => ['nullable', 'integer', 'min:0', 'max:20'],
            'luong_thuong_phu_cap' => ['nullable', 'array'],
            'luong_thuong_phu_cap.*.name' => ['nullable', 'string', 'max:255'],
            'luong_thuong_phu_cap.*.value' => ['nullable', 'numeric', 'min:0'],
            'luong_thuong_phu_cap.*.note' => ['nullable', 'string', 'max:1000'],
            'luong_thuong_phu_cap.luong_theo_dich_vu.items' => ['nullable', 'array'],
            'luong_thuong_phu_cap.luong_theo_dich_vu.items.*.id' => ['nullable', 'integer', 'min:1'],
            'luong_thuong_phu_cap.luong_theo_dich_vu.items.*.ten_dich_vu' => ['nullable', 'string', 'max:255'],
            'luong_thuong_phu_cap.luong_theo_dich_vu.items.*.chup' => ['nullable', 'array'],
            'luong_thuong_phu_cap.luong_theo_dich_vu.items.*.chup.*' => ['nullable', 'numeric', 'min:0'],
            'luong_thuong_phu_cap.luong_theo_dich_vu.items.*.make' => ['nullable', 'array'],
            'luong_thuong_phu_cap.luong_theo_dich_vu.items.*.make.*' => ['nullable', 'numeric', 'min:0'],
            'luong_thuong_phu_cap.luong_theo_dich_vu.items.*.quay_phim' => ['nullable', 'array'],
            'luong_thuong_phu_cap.luong_theo_dich_vu.items.*.quay_phim.*' => ['nullable', 'numeric', 'min:0'],
        ], [
            'phone.regex' => 'Số điện thoại không hợp lệ (VD: 0912345678).',
            'phone.unique' => 'Số điện thoại đã được sử dụng.',
            'email.unique' => 'Email đã được sử dụng.',
            'cccd.unique' => 'CCCD đã được sử dụng.',
            'password.required' => 'Vui lòng nhập mật khẩu.',
        ]);
    }

    /**
     * Lấy các field thuộc bảng nhan_vien từ payload đã validate.
     *
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function nhanVienAttributes(array $validated): array
    {
        $keys = [
            'hinh_anh',
            'phong_ban_ids',
            'vai_tro_id',
            'ngan_hang',
            'chi_nhanh',
            'so_tai_khoan',
            'chu_tai_khoan',
            'gioi_tinh',
            'ngay_sinh',
            'cccd',
            'vi_tri_lam_viec',
            'ngay_vao_cong_ty',
            'ngay_ky_hop_dong',
            'loai_nhan_vien',
            'loai_hop_dong',
            'cong_chuan',
            'tham_gia_bao_hiem',
            'so_nguoi_phu_thuoc',
            'luong_thuong_phu_cap',
        ];

        $data = [];
        foreach ($keys as $key) {
            if (array_key_exists($key, $validated)) {
                $data[$key] = $validated[$key];
            }
        }

        $data['tham_gia_bao_hiem'] = (bool) ($validated['tham_gia_bao_hiem'] ?? false);
        $data['so_nguoi_phu_thuoc'] = (int) ($validated['so_nguoi_phu_thuoc'] ?? 0);

        if (array_key_exists('phong_ban_ids', $data)) {
            $ids = is_array($data['phong_ban_ids']) ? $data['phong_ban_ids'] : [];
            $data['phong_ban_ids'] = array_values(array_unique(array_map('intval', $ids))) ?: null;
        }

        if (array_key_exists('vai_tro_id', $data) && ($data['vai_tro_id'] === '' || $data['vai_tro_id'] === null)) {
            $data['vai_tro_id'] = null;
        }

        if (array_key_exists('luong_thuong_phu_cap', $data)) {
            $data['luong_thuong_phu_cap'] = $this->normalizeLuongThuongPhuCap($data['luong_thuong_phu_cap']);
        }

        // Chuỗi rỗng → null cho các field nullable
        foreach (['hinh_anh', 'ngan_hang', 'chi_nhanh', 'so_tai_khoan', 'chu_tai_khoan', 'cccd', 'vi_tri_lam_viec', 'gioi_tinh'] as $nullable) {
            if (array_key_exists($nullable, $data) && $data[$nullable] === '') {
                $data[$nullable] = null;
            }
        }

        if (array_key_exists('hinh_anh', $data)) {
            $data['hinh_anh'] = Media::normalizePath($data['hinh_anh']);
        }

        return $data;
    }

    /**
     * Chuẩn hóa object JSON lương/thưởng/phụ cấp.
     *
     * @param  mixed  $input
     * @return array<string, mixed>|null
     */
    private function normalizeLuongThuongPhuCap(mixed $input): ?array
    {
        if (! is_array($input) || $input === []) {
            return null;
        }

        $definitions = \App\Models\NhanVien::salaryFieldDefinitions();
        $defaultNotes = \App\Models\NhanVien::salaryFieldNotes();
        $defaultValues = \App\Models\NhanVien::salaryFieldDefaultValues();
        $result = [];

        foreach ($definitions as $key => $defaultName) {
            $item = $input[$key] ?? null;

            if ($key === 'luong_theo_dich_vu') {
                $result[$key] = $this->normalizeLuongTheoDichVu($item, $defaultName);
                continue;
            }

            $defaultNote = $defaultNotes[$key] ?? null;
            $fieldDefaultValue = $defaultValues[$key] ?? null;
            if (! is_array($item)) {
                $result[$key] = [
                    'name' => $defaultName,
                    'value' => $fieldDefaultValue,
                    'note' => $defaultNote,
                ];
                continue;
            }

            $value = $item['value'] ?? null;
            $note = $item['note'] ?? null;
            $normalizedNote = $note === null || $note === '' ? null : trim((string) $note);

            $result[$key] = [
                'name' => trim((string) ($item['name'] ?? $defaultName)) ?: $defaultName,
                'value' => $value === null || $value === ''
                    ? $fieldDefaultValue
                    : (float) $value,
                'note' => $normalizedNote ?? $defaultNote,
            ];
        }

        return $result;
    }

    /**
     * @return array{name: string, items: list<array<string, mixed>>}
     */
    private function normalizeLuongTheoDichVu(mixed $item, string $defaultName): array
    {
        $rows = [];
        if (is_array($item)) {
            $rows = $item['items'] ?? [];
            if (! is_array($rows)) {
                $rows = [];
            }
        }

        if ($rows !== [] && ! array_is_list($rows)) {
            $rows = array_values($rows);
        }

        $roles = \App\Models\NhanVien::salaryDichVuRoles();
        $items = [];

        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }
            $id = (int) ($row['id'] ?? $row['danh_muc_loai_quay_chup_id'] ?? 0);
            if ($id <= 0) {
                continue;
            }

            $entry = [
                'id' => $id,
                'ten_dich_vu' => trim((string) ($row['ten_dich_vu'] ?? '')) ?: null,
            ];
            foreach ($roles as $role) {
                $entry[$role] = $this->normalizeDiemMap($row[$role] ?? null);
            }
            $items[] = $entry;
        }

        return [
            'name' => $defaultName,
            'items' => $items,
        ];
    }

    /**
     * @return array{1: float|null, 2: float|null, 3: float|null}
     */
    private function normalizeDiemMap(mixed $input): array
    {
        $result = [1 => null, 2 => null, 3 => null];
        if (! is_array($input)) {
            return $result;
        }

        foreach ([1, 2, 3] as $level) {
            $amount = $input[$level] ?? $input[(string) $level] ?? null;
            $result[$level] = $amount === null || $amount === '' ? null : (float) $amount;
        }

        return $result;
    }
}
