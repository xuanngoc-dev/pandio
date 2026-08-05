<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Các cột lương/thưởng/phụ cấp được gom vào JSON luong_thuong_phu_cap.
     *
     * @var array<string, string>
     */
    private const SALARY_FIELDS = [
        'luong_cung' => 'Lương cứng',
        'luong_mem' => 'Lương mềm',
        'phu_cap' => 'Phụ cấp',
        'luong_co_ban' => 'Lương cơ bản',
        'luong_tang_ca' => 'Lương tăng ca',
        'phu_cap_xang' => 'Phụ cấp xăng',
        'phu_cap_an_trua' => 'Phụ cấp ăn trưa',
        'phu_cap_dien_thoai' => 'Phụ cấp điện thoại',
        'phu_cap_nha_o' => 'Phụ cấp nhà ở',
        'thuong_chuyen_can' => 'Thưởng chuyên cần',
        'hoa_hong_hop_dong_cuoi' => 'Hoa hồng HĐ cuối',
        'hoa_hong_hop_dong_trang_phuc' => 'Hoa hồng HĐ trang phục',
    ];

    public function up(): void
    {
        Schema::table('nhan_vien', function (Blueprint $table) {
            $table->json('luong_thuong_phu_cap')->nullable()->after('so_nguoi_phu_thuoc');
        });

        $columns = array_keys(self::SALARY_FIELDS);
        $select = array_merge(['id'], $columns);

        DB::table('nhan_vien')->orderBy('id')->select($select)->chunkById(100, function ($rows) {
            foreach ($rows as $row) {
                $payload = [];
                foreach (self::SALARY_FIELDS as $column => $name) {
                    $raw = $row->{$column} ?? null;
                    [$jsonKey, $jsonName, $jsonNote] = match ($column) {
                        'hoa_hong_hop_dong_cuoi' => ['hoa_hong_hop_dong_sddv', 'Hoa hồng HĐ sử dụng dịch vụ', null],
                        'luong_co_ban' => ['luong_1_gio', 'Lương 1 giờ', 'Dành cho part time'],
                        'luong_tang_ca' => ['luong_tang_ca_1_gio', 'Lương tăng ca 1 giờ', 'Dành cho cả part_time và full_time'],
                        default => [$column, $name, null],
                    };
                    $payload[$jsonKey] = [
                        'name' => $jsonName,
                        'value' => $raw !== null ? (float) $raw : null,
                        'note' => $jsonNote,
                    ];
                }

                DB::table('nhan_vien')
                    ->where('id', $row->id)
                    ->update([
                        'luong_thuong_phu_cap' => json_encode($payload, JSON_UNESCAPED_UNICODE),
                    ]);
            }
        });

        Schema::table('nhan_vien', function (Blueprint $table) {
            $table->dropColumn(array_keys(self::SALARY_FIELDS));
        });
    }

    public function down(): void
    {
        Schema::table('nhan_vien', function (Blueprint $table) {
            foreach (array_keys(self::SALARY_FIELDS) as $column) {
                $table->decimal($column, 15, 2)->nullable();
            }
        });

        DB::table('nhan_vien')->orderBy('id')->select(['id', 'luong_thuong_phu_cap'])->chunkById(100, function ($rows) {
            foreach ($rows as $row) {
                $data = is_string($row->luong_thuong_phu_cap)
                    ? json_decode($row->luong_thuong_phu_cap, true)
                    : (is_array($row->luong_thuong_phu_cap) ? $row->luong_thuong_phu_cap : []);

                $update = [];
                foreach (array_keys(self::SALARY_FIELDS) as $column) {
                    $jsonKey = match ($column) {
                        'hoa_hong_hop_dong_cuoi' => 'hoa_hong_hop_dong_sddv',
                        'luong_co_ban' => 'luong_1_gio',
                        'luong_tang_ca' => 'luong_tang_ca_1_gio',
                        default => $column,
                    };
                    $value = $data[$jsonKey]['value'] ?? null;
                    $update[$column] = $value !== null && $value !== '' ? $value : null;
                }

                DB::table('nhan_vien')->where('id', $row->id)->update($update);
            }
        });

        Schema::table('nhan_vien', function (Blueprint $table) {
            $table->dropColumn('luong_thuong_phu_cap');
        });
    }
};
