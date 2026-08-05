<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('danh_muc_loai_thong_bao', function (Blueprint $table) {
            $table->id();
            $table->string('ma_loai_thong_bao')->unique();
            $table->string('ten_loai_thong_bao');
            $table->string('icon')->nullable();
            $table->enum('trang_thai', ['dang_su_dung', 'ngung_su_dung'])->default('dang_su_dung');
            $table->timestamps();
        });

        $now = now();

        DB::table('danh_muc_loai_thong_bao')->insert([
            [
                'ma_loai_thong_bao' => 'deal_updated',
                'ten_loai_thong_bao' => 'Cập nhật deal',
                'icon' => 'handshake',
                'trang_thai' => 'dang_su_dung',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'ma_loai_thong_bao' => 'task_assigned',
                'ten_loai_thong_bao' => 'Giao việc',
                'icon' => 'clipboard-list',
                'trang_thai' => 'dang_su_dung',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'ma_loai_thong_bao' => 'comment_added',
                'ten_loai_thong_bao' => 'Bình luận mới',
                'icon' => 'message-circle',
                'trang_thai' => 'dang_su_dung',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'ma_loai_thong_bao' => 'lead_created',
                'ten_loai_thong_bao' => 'Lead mới',
                'icon' => 'user-plus',
                'trang_thai' => 'dang_su_dung',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'ma_loai_thong_bao' => 'leave_request',
                'ten_loai_thong_bao' => 'Đăng ký nghỉ phép',
                'icon' => 'calendar-off',
                'trang_thai' => 'dang_su_dung',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'ma_loai_thong_bao' => 'attendance_reminder',
                'ten_loai_thong_bao' => 'Nhắc chấm công',
                'icon' => 'clock',
                'trang_thai' => 'dang_su_dung',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'ma_loai_thong_bao' => 'contract_approved',
                'ten_loai_thong_bao' => 'Duyệt hợp đồng',
                'icon' => 'file-check',
                'trang_thai' => 'dang_su_dung',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'ma_loai_thong_bao' => 'payment_received',
                'ten_loai_thong_bao' => 'Nhận thanh toán',
                'icon' => 'wallet',
                'trang_thai' => 'dang_su_dung',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'ma_loai_thong_bao' => 'system_announcement',
                'ten_loai_thong_bao' => 'Thông báo hệ thống',
                'icon' => 'megaphone',
                'trang_thai' => 'dang_su_dung',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'ma_loai_thong_bao' => 'mention_user',
                'ten_loai_thong_bao' => 'Được nhắc đến',
                'icon' => 'at-sign',
                'trang_thai' => 'dang_su_dung',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);

        Schema::create('he_thong_thong_bao', function (Blueprint $table) {
            $table->id();
            $table->json('nguoi_nhan_ids');
            $table->foreignId('actor_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->foreignId('loai_thong_bao_id')
                ->constrained('danh_muc_loai_thong_bao')
                ->restrictOnDelete();
            $table->string('loai_mau_sac', 32)->default('blue');
            $table->string('tieu_de');
            $table->text('noi_dung')->nullable();
            $table->json('nguoi_nhan_da_doc_ids')->nullable();
            $table->json('nguoi_dung_da_xoa_ids')->nullable();
            $table->unsignedTinyInteger('muc_do_uu_tien')->default(1);
            $table->json('du_lieu')->nullable();
            $table->timestamps();

            $table->index('loai_thong_bao_id');
            $table->index('actor_id');
            $table->index('muc_do_uu_tien');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('he_thong_thong_bao');
        Schema::dropIfExists('danh_muc_loai_thong_bao');
    }
};
