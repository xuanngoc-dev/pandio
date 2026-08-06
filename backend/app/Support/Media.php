<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

/**
 * Chuẩn hóa đường dẫn media public storage.
 * DB lưu path tương đối hoặc URL ngoài (https://...);
 * API JSON trả URL tuyệt đối (https://domain/storage/... hoặc giữ nguyên URL ngoài).
 */
class Media
{
    /**
     * Path tương đối / URL → URL public tuyệt đối.
     * - Path tương đối: ghép APP_URL/storage/...
     * - URL có /storage/: normalize rồi ghép lại từ APP_URL (tránh domain sai)
     * - URL ngoài (không có /storage/): giữ nguyên
     */
    public static function url(?string $path): ?string
    {
        if ($path === null) {
            return null;
        }

        $value = trim($path);
        if ($value === '') {
            return null;
        }

        // Absolute / protocol-relative URL ngoài → giữ nguyên
        if (self::isExternalAbsoluteUrl($value)) {
            return $value;
        }

        $normalized = self::normalizePath($value);
        if ($normalized === null || $normalized === '') {
            return null;
        }

        // normalizePath có thể trả lại URL ngoài
        if (self::isExternalAbsoluteUrl($normalized)) {
            return $normalized;
        }

        return Storage::disk('public')->url($normalized);
    }

    /**
     * URL tuyệt đối / path có prefix storage → path lưu DB (vd: concept/abc.jpg).
     * URL ngoài (CDN, logo ngân hàng, …) giữ nguyên full URL.
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
            $urlPath = parse_url($value, PHP_URL_PATH) ?: '';
            if (preg_match('#/storage/(.+)$#', $urlPath, $matches)) {
                return $matches[1];
            }

            // URL ngoài: giữ nguyên (không strip domain)
            return $value;
        }

        return ltrim((string) preg_replace('#^/?storage/#', '', $value), '/');
    }

    /**
     * Xóa file trên disk public (bỏ qua nếu path rỗng / URL ngoài / không tồn tại).
     */
    public static function delete(?string $path): void
    {
        $normalized = self::normalizePath($path);
        if (! $normalized || self::isExternalAbsoluteUrl($normalized)) {
            return;
        }

        if (Storage::disk('public')->exists($normalized)) {
            Storage::disk('public')->delete($normalized);
        }
    }

    /**
     * URL tuyệt đối không thuộc public storage của app.
     */
    private static function isExternalAbsoluteUrl(string $value): bool
    {
        if (! preg_match('#^(https?:)?//#i', $value)) {
            return false;
        }

        $urlPath = parse_url($value, PHP_URL_PATH) ?: '';

        return ! preg_match('#/storage/#i', $urlPath);
    }
}
