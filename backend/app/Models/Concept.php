<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

#[Fillable([
    'hinh_anh',
    'loai_concept',
    'ma_concept',
    'ten_concept',
    'dia_diem',
    'trang_thai',
    'mo_ta',
])]
class Concept extends Model
{
    protected $table = 'concept';

    public function danhMuc(): BelongsTo
    {
        return $this->belongsTo(DanhMucConcept::class, 'loai_concept');
    }

    /**
     * API trả hinh_anh dạng URL tuyệt đối (https://domain/storage/...).
     * DB vẫn lưu đường dẫn tương đối.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $array = parent::toArray();
        $raw = $this->attributes['hinh_anh'] ?? null;
        if (is_string($raw) && $raw !== '') {
            $array['hinh_anh'] = static::toPublicUrl($raw);
        }

        return $array;
    }

    /**
     * Đường dẫn storage / URL → URL public tuyệt đối.
     */
    public static function toPublicUrl(string $path): string
    {
        if (preg_match('#^(https?:)?//#i', $path)) {
            return $path;
        }

        return Storage::disk('public')->url(ltrim($path, '/'));
    }

    /**
     * URL tuyệt đối / path có prefix storage → path lưu DB (vd: concept/abc.jpg).
     */
    public static function normalizeHinhAnhPath(?string $value): ?string
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
}
