<?php

namespace App\Services;

use App\Support\Media;
use Illuminate\Http\File as LocalFile;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use ZipArchive;

class TrangPhucHinhAnhFileService
{
    public const IMAGE_MAX_BYTES = 5 * 1024 * 1024;

    public const ZIP_MAX_BYTES = 1024 * 1024 * 1024;

    public const CHUNK_MAX_KILOBYTES = 1536;

    public const EXTRACT_BATCH = 40;

    public const ZIP_ENTRY_MAX_BYTES = 20 * 1024 * 1024;

    public function __construct(
        private readonly string $folder = 'trang-phuc',
    ) {}

    /**
     * @return list<string>
     */
    public function imageExtensions(): array
    {
        return ['jpg', 'jpeg', 'png', 'webp', 'gif', 'bmp', 'svg'];
    }

    /**
     * @return list<string>
     */
    public function listExtensions(): array
    {
        return array_merge($this->imageExtensions(), ['zip']);
    }

    public function sanitizeName(string $name): string
    {
        $name = str_replace(["\0", '/', '\\'], '', trim($name));
        $name = basename($name);
        $name = preg_replace('/[:*?"<>|]/u', '', $name) ?? '';
        $name = trim($name);

        return ltrim($name, '.');
    }

    /**
     * @return array{upload_id: string, received: int, total_chunks: int}
     */
    public function storeChunk(
        string $filename,
        int $chunkIndex,
        int $totalChunks,
        int $totalSize,
        UploadedFile $chunk,
        ?string $uploadId = null,
    ): array {
        $filename = $this->sanitizeName($filename);
        $kind = $this->kindFromName($filename);

        if ($kind === null) {
            throw ValidationException::withMessages([
                'filename' => 'Chỉ chấp nhận ảnh (jpg, jpeg, png, webp, gif, bmp, svg) hoặc file zip.',
            ]);
        }

        $maxSize = $kind === 'zip' ? self::ZIP_MAX_BYTES : self::IMAGE_MAX_BYTES;
        if ($totalSize > $maxSize) {
            throw ValidationException::withMessages([
                'total_size' => $kind === 'zip'
                    ? 'File zip tối đa 1GB.'
                    : 'Mỗi ảnh tối đa 5MB.',
            ]);
        }

        if ($chunkIndex >= $totalChunks) {
            throw ValidationException::withMessages([
                'chunk_index' => 'Phần chunk không hợp lệ.',
            ]);
        }

        if ($uploadId) {
            $meta = $this->readMeta($uploadId);
            if (($meta['filename'] ?? '') !== $filename
                || (int) ($meta['total_chunks'] ?? 0) !== $totalChunks
                || (int) ($meta['total_size'] ?? 0) !== $totalSize
            ) {
                throw ValidationException::withMessages([
                    'upload_id' => 'Thông tin upload không khớp.',
                ]);
            }
        } else {
            $uploadId = (string) Str::uuid();
            $this->writeMeta($uploadId, [
                'filename' => $filename,
                'kind' => $kind,
                'total_chunks' => $totalChunks,
                'total_size' => $totalSize,
            ]);
        }

        $chunk->move($this->chunkDir($uploadId), (string) $chunkIndex);

        $received = $this->receivedChunkCount($uploadId);

        return [
            'upload_id' => $uploadId,
            'received' => $received,
            'total_chunks' => $totalChunks,
        ];
    }

    /**
     * @return array{name: string, path: string, url: string, kind: string, size: int}
     */
    public function completeUpload(string $uploadId): array
    {
        $meta = $this->readMeta($uploadId);
        $filename = (string) ($meta['filename'] ?? '');
        $kind = (string) ($meta['kind'] ?? '');
        $totalChunks = (int) ($meta['total_chunks'] ?? 0);
        $totalSize = (int) ($meta['total_size'] ?? 0);

        if ($filename === '' || $totalChunks < 1) {
            throw ValidationException::withMessages([
                'upload_id' => 'Phiên tải lên không hợp lệ.',
            ]);
        }

        if ($this->receivedChunkCount($uploadId) !== $totalChunks) {
            throw ValidationException::withMessages([
                'upload_id' => 'Chưa nhận đủ các phần của file.',
            ]);
        }

        $assembled = $this->chunkDir($uploadId).'/assembled';
        $out = fopen($assembled, 'wb');
        if ($out === false) {
            throw ValidationException::withMessages([
                'upload_id' => 'Không ghép được file tải lên.',
            ]);
        }

        try {
            for ($i = 0; $i < $totalChunks; $i++) {
                $chunkPath = $this->chunkDir($uploadId).'/'.$i;
                if (! is_file($chunkPath)) {
                    throw ValidationException::withMessages([
                        'upload_id' => 'Thiếu phần file số '.($i + 1).'.',
                    ]);
                }

                $in = fopen($chunkPath, 'rb');
                if ($in === false) {
                    throw ValidationException::withMessages([
                        'upload_id' => 'Không đọc được phần file số '.($i + 1).'.',
                    ]);
                }
                stream_copy_to_stream($in, $out);
                fclose($in);
            }
        } finally {
            fclose($out);
        }

        $assembledSize = (int) filesize($assembled);
        if ($totalSize > 0 && $assembledSize !== $totalSize) {
            $this->forgetUpload($uploadId);
            throw ValidationException::withMessages([
                'upload_id' => 'Dung lượng file sau khi ghép không khớp.',
            ]);
        }

        $originalName = $filename;
        if ($kind === 'zip') {
            $filename = $this->uniqueZipName($filename);
        }

        $this->putLocalFile($filename, $assembled);
        $this->forgetUpload($uploadId);

        $path = $this->relativePath($filename);

        return [
            'name' => $filename,
            'original_name' => $originalName,
            'renamed' => $filename !== $originalName,
            'path' => $path,
            'url' => Media::url($path),
            'kind' => $kind,
            'size' => $assembledSize,
        ];
    }

    /**
     * @return array{extracted: int, skipped: int, total: int, cursor: int, done: bool, names: list<string>}
     */
    public function extractZip(string $path, int $cursor = 0, int $limit = self::EXTRACT_BATCH): array
    {
        $resolved = $this->resolveFile($path);
        if ($resolved === null) {
            throw ValidationException::withMessages([
                'path' => 'Không tìm thấy file zip.',
            ]);
        }

        $ext = strtolower((string) pathinfo($resolved['name'], PATHINFO_EXTENSION));
        if ($ext !== 'zip') {
            throw ValidationException::withMessages([
                'path' => 'File không phải zip.',
            ]);
        }

        $absolute = $resolved['location'] === 'public'
            ? (string) ($resolved['absolute'] ?? '')
            : Storage::disk('public')->path($resolved['path']);

        if ($absolute === '' || ! is_file($absolute)) {
            throw ValidationException::withMessages([
                'path' => 'Không tìm thấy file zip.',
            ]);
        }

        $zip = new ZipArchive;
        if ($zip->open($absolute) !== true) {
            throw ValidationException::withMessages([
                'path' => 'Không đọc được file zip.',
            ]);
        }

        try {
            $imageIndexes = [];
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $stat = $zip->statIndex($i);
                $entryName = (string) ($stat['name'] ?? $zip->getNameIndex($i));
                $size = (int) ($stat['size'] ?? 0);
                if ($this->isExtractableImageEntry($entryName, $size)) {
                    $imageIndexes[] = $i;
                }
            }

            $total = count($imageIndexes);
            $slice = array_slice($imageIndexes, $cursor, max(1, $limit));
            $extracted = 0;
            $skipped = 0;
            $names = [];

            foreach ($slice as $index) {
                $entryName = (string) $zip->getNameIndex($index);
                $base = $this->sanitizeName(basename(str_replace('\\', '/', $entryName)));
                $extName = strtolower((string) pathinfo($base, PATHINFO_EXTENSION));
                if ($base === '' || ! in_array($extName, $this->imageExtensions(), true)) {
                    $skipped++;

                    continue;
                }

                $stream = $zip->getStream($entryName);
                if ($stream === false) {
                    $skipped++;

                    continue;
                }

                $contents = stream_get_contents($stream);
                fclose($stream);

                if ($contents === false || $contents === '') {
                    $skipped++;

                    continue;
                }

                $this->putContents($base, $contents);
                $extracted++;
                $names[] = $base;
            }

            $next = $cursor + count($slice);

            return [
                'extracted' => $extracted,
                'skipped' => $skipped,
                'total' => $total,
                'cursor' => $next,
                'done' => $next >= $total,
                'names' => $names,
            ];
        } finally {
            $zip->close();
        }
    }

    /**
     * @return array{location: string, name: string, path: string, url: string, absolute?: string}|null
     */
    public function resolveFile(string $path): ?array
    {
        $normalized = Media::normalizePath($path) ?: ltrim($path, '/');
        $name = basename(str_replace('\\', '/', $normalized));

        if ($name === '' || $name === '.' || $name === '..' || str_contains($name, '/') || str_contains($name, '\\')) {
            return null;
        }

        $relative = $this->relativePath($name);
        $publicFile = public_path($relative);
        if (is_file($publicFile)) {
            return [
                'location' => 'public',
                'name' => $name,
                'path' => $relative,
                'url' => asset($relative),
                'absolute' => $publicFile,
            ];
        }

        if (Storage::disk('public')->exists($relative)) {
            return [
                'location' => 'storage',
                'name' => $name,
                'path' => $relative,
                'url' => Media::url($relative),
            ];
        }

        return null;
    }

    public function putLocalFile(string $name, string $absoluteSource): void
    {
        $this->deletePublicDuplicate($name);
        Storage::disk('public')->putFileAs($this->folder, new LocalFile($absoluteSource), $name);
    }

    public function putContents(string $name, string $contents): void
    {
        $this->deletePublicDuplicate($name);
        Storage::disk('public')->put($this->relativePath($name), $contents);
    }

    /**
     * Xóa file theo path (cả public và storage nếu trùng tên).
     *
     * @param  list<string>  $paths
     * @return array{deleted: list<string>, count: int}
     */
    public function deleteFiles(array $paths): array
    {
        $deleted = [];
        $seen = [];

        foreach ($paths as $path) {
            $resolved = $this->resolveFile((string) $path);
            if ($resolved === null) {
                continue;
            }

            $name = $resolved['name'];
            $key = mb_strtolower($name);
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;

            $this->deleteByName($name);
            $deleted[] = $name;
        }

        return [
            'deleted' => $deleted,
            'count' => count($deleted),
        ];
    }

    /**
     * File ảnh/zip trong public/{folder} và storage public disk.
     *
     * @return \Illuminate\Support\Collection<int, array{name: string, path: string, url: string, size: int, modified_at: string, kind: string}>
     */
    public function listFiles(): \Illuminate\Support\Collection
    {
        $extensions = $this->listExtensions();
        $items = collect();

        $publicDir = $this->publicDir();
        if (is_dir($publicDir)) {
            foreach (File::files($publicDir) as $file) {
                $ext = strtolower($file->getExtension());
                if (! in_array($ext, $extensions, true)) {
                    continue;
                }

                $name = $file->getFilename();
                $path = $this->relativePath($name);

                $items->push([
                    'name' => $name,
                    'path' => $path,
                    'url' => asset($path),
                    'size' => $file->getSize(),
                    'modified_at' => date('c', $file->getMTime()),
                    'kind' => $ext === 'zip' ? 'zip' : 'image',
                ]);
            }
        }

        foreach (Storage::disk('public')->files($this->folder) as $storagePath) {
            $ext = strtolower(pathinfo($storagePath, PATHINFO_EXTENSION));
            if (! in_array($ext, $extensions, true)) {
                continue;
            }

            $items->push([
                'name' => basename($storagePath),
                'path' => $storagePath,
                'url' => Media::url($storagePath),
                'size' => Storage::disk('public')->size($storagePath),
                'modified_at' => date('c', Storage::disk('public')->lastModified($storagePath)),
                'kind' => $ext === 'zip' ? 'zip' : 'image',
            ]);
        }

        return $items
            ->unique(fn (array $item) => mb_strtolower($item['name']))
            ->values();
    }

    /**
     * @return array{name: string, path: string, url: string}
     */
    public function rename(string $path, string $newName): array
    {
        $resolved = $this->resolveFile($path);
        if ($resolved === null) {
            throw ValidationException::withMessages([
                'path' => 'Không tìm thấy hình ảnh.',
            ]);
        }

        $oldName = $resolved['name'];
        $newName = $this->sanitizeName($newName);
        $oldExt = strtolower((string) pathinfo($oldName, PATHINFO_EXTENSION));
        $allowedExt = $oldExt === 'zip' ? ['zip'] : $this->imageExtensions();
        $newExt = strtolower((string) pathinfo($newName, PATHINFO_EXTENSION));
        $newStem = (string) pathinfo($newName, PATHINFO_FILENAME);

        if ($newStem === '' || $newExt === '') {
            throw ValidationException::withMessages([
                'name' => 'Tên file phải gồm tên và đuôi (vd: concept-studio.jpg).',
            ]);
        }

        if (! in_array($newExt, $allowedExt, true)) {
            throw ValidationException::withMessages([
                'name' => 'Đuôi file không hợp lệ. Chỉ chấp nhận: '.implode(', ', $allowedExt).'.',
            ]);
        }

        $newName = $newStem.'.'.$newExt;

        if ($newName === $oldName) {
            return [
                'name' => $oldName,
                'path' => $resolved['path'],
                'url' => $resolved['url'],
            ];
        }

        $isSameFile = mb_strtolower($newName) === mb_strtolower($oldName);
        if (! $isSameFile && $this->nameExists($newName)) {
            throw ValidationException::withMessages([
                'name' => 'Tên file đã tồn tại. Vui lòng chọn tên khác.',
            ]);
        }

        $newPath = $this->relativePath($newName);

        if (($resolved['location'] ?? '') === 'public') {
            $this->moveLocalFile((string) ($resolved['absolute'] ?? ''), public_path($newPath));
        } else {
            $this->moveStorageFile($resolved['path'], $newPath);
        }

        return [
            'name' => $newName,
            'path' => $newPath,
            'url' => ($resolved['location'] ?? '') === 'public'
                ? asset($newPath)
                : Media::url($newPath),
        ];
    }

    public function uniqueZipName(string $name): string
    {
        $name = $this->sanitizeName($name);
        if ($name === '' || ! $this->nameExists($name)) {
            return $name;
        }

        $stem = (string) pathinfo($name, PATHINFO_FILENAME);
        $ext = (string) pathinfo($name, PATHINFO_EXTENSION);
        $i = 1;

        do {
            $candidate = $stem.'('.$i.').'.$ext;
            $i++;
        } while ($this->nameExists($candidate) && $i < 10000);

        return $candidate;
    }

    public function nameExists(string $name): bool
    {
        $target = mb_strtolower(basename($name));

        $publicDir = $this->publicDir();
        if (is_dir($publicDir)) {
            foreach (File::files($publicDir) as $file) {
                if (mb_strtolower($file->getFilename()) === $target) {
                    return true;
                }
            }
        }

        foreach (Storage::disk('public')->files($this->folder) as $storagePath) {
            if (mb_strtolower(basename($storagePath)) === $target) {
                return true;
            }
        }

        return false;
    }

    private function deleteByName(string $name): void
    {
        $target = mb_strtolower($name);

        $publicDir = $this->publicDir();
        if (is_dir($publicDir)) {
            foreach (File::files($publicDir) as $file) {
                if (mb_strtolower($file->getFilename()) === $target) {
                    File::delete($file->getPathname());
                }
            }
        }

        foreach (Storage::disk('public')->files($this->folder) as $path) {
            if (mb_strtolower(basename($path)) === $target) {
                Storage::disk('public')->delete($path);
            }
        }
    }

    private function deletePublicDuplicate(string $name): void
    {
        $publicFile = public_path($this->relativePath($name));
        if (is_file($publicFile)) {
            File::delete($publicFile);
        }
    }

    private function relativePath(string $name): string
    {
        return $this->folder.'/'.$name;
    }

    private function publicDir(): string
    {
        return public_path($this->folder);
    }

    private function moveLocalFile(string $from, string $to): void
    {
        if ($from === '' || $from === $to) {
            return;
        }

        $dir = dirname($to);
        if (! is_dir($dir)) {
            File::makeDirectory($dir, 0755, true);
        }

        if (strcasecmp($from, $to) === 0) {
            $tmp = $from.'.tmp-'.bin2hex(random_bytes(4));
            File::move($from, $tmp);
            File::move($tmp, $to);

            return;
        }

        File::move($from, $to);
    }

    private function moveStorageFile(string $from, string $to): void
    {
        if ($from === $to) {
            return;
        }

        $disk = Storage::disk('public');

        if (strcasecmp($from, $to) === 0) {
            $tmp = $from.'.tmp-'.bin2hex(random_bytes(4));
            $disk->move($from, $tmp);
            $disk->move($tmp, $to);

            return;
        }

        $disk->move($from, $to);
    }

    private function kindFromName(string $name): ?string
    {
        $ext = strtolower((string) pathinfo($name, PATHINFO_EXTENSION));
        if ($ext === 'zip') {
            return 'zip';
        }

        if (in_array($ext, $this->imageExtensions(), true)) {
            return 'image';
        }

        return null;
    }

    private function isExtractableImageEntry(string $entryName, int $size): bool
    {
        $normalized = str_replace('\\', '/', $entryName);
        if ($normalized === '' || str_ends_with($normalized, '/')) {
            return false;
        }

        if (str_contains($normalized, '__MACOSX') || str_contains($normalized, '..')) {
            return false;
        }

        $base = basename($normalized);
        if ($base === '' || str_starts_with($base, '.') || str_starts_with($base, '._')) {
            return false;
        }

        $ext = strtolower((string) pathinfo($base, PATHINFO_EXTENSION));
        if (! in_array($ext, $this->imageExtensions(), true)) {
            return false;
        }

        return $size > 0 && $size <= self::ZIP_ENTRY_MAX_BYTES;
    }

    /**
     * @return array<string, mixed>
     */
    private function readMeta(string $uploadId): array
    {
        $this->assertUploadId($uploadId);
        $metaPath = $this->chunkDir($uploadId).'/meta.json';
        if (! is_file($metaPath)) {
            throw ValidationException::withMessages([
                'upload_id' => 'Không tìm thấy phiên tải lên.',
            ]);
        }

        $decoded = json_decode((string) file_get_contents($metaPath), true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    private function writeMeta(string $uploadId, array $meta): void
    {
        $dir = $this->chunkDir($uploadId);
        if (! is_dir($dir)) {
            File::makeDirectory($dir, 0755, true);
        }

        file_put_contents($dir.'/meta.json', json_encode($meta));
    }

    private function receivedChunkCount(string $uploadId): int
    {
        $dir = $this->chunkDir($uploadId);
        if (! is_dir($dir)) {
            return 0;
        }

        $count = 0;
        foreach (File::files($dir) as $file) {
            if (preg_match('/^\d+$/', $file->getFilename())) {
                $count++;
            }
        }

        return $count;
    }

    private function forgetUpload(string $uploadId): void
    {
        $dir = $this->chunkDir($uploadId);
        if (is_dir($dir)) {
            File::deleteDirectory($dir);
        }
    }

    private function chunkDir(string $uploadId): string
    {
        $this->assertUploadId($uploadId);

        return storage_path('app/hinh-anh-chunks/'.$uploadId);
    }

    private function assertUploadId(string $uploadId): void
    {
        if (! Str::isUuid($uploadId)) {
            throw ValidationException::withMessages([
                'upload_id' => 'Mã phiên tải lên không hợp lệ.',
            ]);
        }
    }
}
