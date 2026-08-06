<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

/**
 * Chuẩn hóa đường dẫn media public storage.
 * DB lưu path tương đối; API JSON trả URL tuyệt đối (https://domain/storage/...).
 */
class Media
{
    /**
     * Path tương đối / URL → URL public tuyệt đối.
     * Luôn normalize rồi ghép lại từ APP_URL (tránh giữ domain sai nếu DB/API từng lưu full URL).
     */
    public static function url(?string $path): ?string
    {
        $normalized = self::normalizePath($path);
        if ($normalized === null || $normalized === '') {
            return null;
        }

        return Storage::disk('public')->url($normalized);
    }

    /**
     * URL tuyệt đối / path có prefix storage → path lưu DB (vd: concept/abc.jpg).
     */
    public static function normalizePath(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim($value);
        if ($value === '') {
            return null;
        }

        if (preg_match('#^(https?:)?//#i', $value)) {
            $path = parse_url($value, PHP_URL_PATH) ?: '';
            if (preg_match('#/storage/(.+)$#', $path, $matches)) {
                return $matches[1];
            }

            return ltrim($path, '/') ?: null;
        }

        return ltrim((string) preg_replace('#^/?storage/#', '', $value), '/');
    }

    /**
     * Xóa file trên disk public (bỏ qua nếu path rỗng / không tồn tại).
     */
    public static function delete(?string $path): void
    {
        $normalized = self::normalizePath($path);
        if (! $normalized) {
            return;
        }

        if (Storage::disk('public')->exists($normalized)) {
            Storage::disk('public')->delete($normalized);
        }
    }
}
