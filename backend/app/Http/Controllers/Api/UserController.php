<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    /**
     * Danh sách user (nhân sự) có phân trang + tìm kiếm.
     *
     * Query: page, per_page, keyword, status
     */
    public function index(Request $request): JsonResponse
    {
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
            ->with(['nhanVien.phongBan'])
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
    }

    /**
     * Chi tiết một nhân sự (kèm nhan_vien).
     */
    public function show(User $user): JsonResponse
    {
        $user->load(['nhanVien.phongBan']);

        return response()->json($user);
    }

    /**
     * Upload hình ảnh nhân viên vào storage/app/public/nhan-vien.
     */
    public function uploadHinhAnh(Request $request): JsonResponse
    {
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
            'url' => Storage::disk('public')->url($path),
        ], 201);
    }

    /**
     * Tạo nhân sự: users + nhan_vien.
     */
    public function store(Request $request): JsonResponse
    {
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

            return $user->load(['nhanVien.phongBan']);
        });

        return response()->json($user, 201);
    }

    /**
     * Cập nhật nhân sự: users + nhan_vien.
     */
    public function update(Request $request, User $user): JsonResponse
    {
        $validated = $this->validatePayload($request, $user);
        $oldHinhAnh = $user->nhanVien?->hinh_anh;

        $user = DB::transaction(function () use ($user, $validated) {
            $userData = [
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'role' => $validated['role'],
                'status' => $validated['status'],
            ];

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

            return $user->fresh()->load(['nhanVien.phongBan']);
        });

        $newHinhAnh = $user->nhanVien?->hinh_anh;
        if ($oldHinhAnh && $oldHinhAnh !== $newHinhAnh) {
            $this->deleteHinhAnhFile($oldHinhAnh);
        }

        return response()->json($user);
    }

    /**
     * Xóa nhân sự (cascade xóa nhan_vien + file ảnh).
     */
    public function destroy(User $user): JsonResponse
    {
        $hinhAnh = $user->nhanVien?->hinh_anh;
        $user->delete();
        $this->deleteHinhAnhFile($hinhAnh);

        return response()->json(['message' => 'Đã xóa nhân sự.']);
    }

    /**
     * Xóa file ảnh trên disk public (nếu thuộc thư mục nhan-vien).
     */
    private function deleteHinhAnhFile(?string $path): void
    {
        if (! $path) {
            return;
        }

        $normalized = ltrim(str_replace('\\', '/', $path), '/');
        if (! str_starts_with($normalized, 'nhan-vien/')) {
            return;
        }

        if (Storage::disk('public')->exists($normalized)) {
            Storage::disk('public')->delete($normalized);
        }
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
            'role' => ['required', 'string', Rule::in(['user', 'admin'])],
            'status' => ['required', 'string', Rule::in(['active', 'inactive'])],

            'hinh_anh' => ['nullable', 'string', 'max:500'],
            'phong_ban_id' => ['nullable', 'integer', 'exists:phong_ban,id'],
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
            'luong_cung' => ['nullable', 'numeric', 'min:0'],
            'luong_mem' => ['nullable', 'numeric', 'min:0'],
            'phu_cap' => ['nullable', 'numeric', 'min:0'],
            'luong_co_ban' => ['nullable', 'numeric', 'min:0'],
            'luong_tang_ca' => ['nullable', 'numeric', 'min:0'],
            'phu_cap_xang' => ['nullable', 'numeric', 'min:0'],
            'phu_cap_an_trua' => ['nullable', 'numeric', 'min:0'],
            'phu_cap_dien_thoai' => ['nullable', 'numeric', 'min:0'],
            'phu_cap_nha_o' => ['nullable', 'numeric', 'min:0'],
            'thuong_chuyen_can' => ['nullable', 'numeric', 'min:0'],
            'hoa_hong_hop_dong_cuoi' => ['nullable', 'numeric', 'min:0'],
            'hoa_hong_hop_dong_trang_phuc' => ['nullable', 'numeric', 'min:0'],
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
            'phong_ban_id',
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
            'luong_cung',
            'luong_mem',
            'phu_cap',
            'luong_co_ban',
            'luong_tang_ca',
            'phu_cap_xang',
            'phu_cap_an_trua',
            'phu_cap_dien_thoai',
            'phu_cap_nha_o',
            'thuong_chuyen_can',
            'hoa_hong_hop_dong_cuoi',
            'hoa_hong_hop_dong_trang_phuc',
        ];

        $data = [];
        foreach ($keys as $key) {
            if (array_key_exists($key, $validated)) {
                $data[$key] = $validated[$key];
            }
        }

        $data['tham_gia_bao_hiem'] = (bool) ($validated['tham_gia_bao_hiem'] ?? false);
        $data['so_nguoi_phu_thuoc'] = (int) ($validated['so_nguoi_phu_thuoc'] ?? 0);

        // Chuỗi rỗng → null cho các field nullable
        foreach (['hinh_anh', 'ngan_hang', 'chi_nhanh', 'so_tai_khoan', 'chu_tai_khoan', 'cccd', 'vi_tri_lam_viec', 'gioi_tinh'] as $nullable) {
            if (array_key_exists($nullable, $data) && $data[$nullable] === '') {
                $data[$nullable] = null;
            }
        }

        return $data;
    }
}
