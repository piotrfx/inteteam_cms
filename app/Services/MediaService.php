<?php

declare(strict_types=1);

namespace App\Services;

use App\DTO\UpdateMediaData;
use App\Models\CmsMedia;
use App\Models\CmsUser;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

final class MediaService
{
    private const ALLOWED_MIMES = [
        'image/jpeg',
        'image/png',
        'image/webp',
        'image/gif',
        'image/svg+xml',
    ];

    private const MAX_BYTES = 10 * 1024 * 1024;  // 10 MB

    private const SVG_MAX_BYTES = 500 * 1024;     // 500 KB

    private const THUMB_SIZE = 400;

    public function upload(CmsUser $uploader, UploadedFile $file): CmsMedia
    {
        $mime = $file->getMimeType() ?? '';

        if (!in_array($mime, self::ALLOWED_MIMES, true)) {
            throw new \InvalidArgumentException("File type not allowed: {$mime}");
        }

        $maxBytes = $mime === 'image/svg+xml' ? self::SVG_MAX_BYTES : self::MAX_BYTES;

        if ($file->getSize() > $maxBytes) {
            throw new \InvalidArgumentException('File exceeds maximum allowed size.');
        }

        /** @var string $disk */
        $disk = config('cms.media_disk', 'local');
        $companyId = $uploader->company_id;
        $ulid = Str::ulid()->toString();
        $ext = strtolower($file->getClientOriginalExtension() ?: 'bin');
        $year = now()->format('Y');
        $month = now()->format('m');
        $path = "media/{$companyId}/{$year}/{$month}/{$ulid}.{$ext}";

        if ($mime === 'image/svg+xml') {
            $content = SvgSanitiser::sanitise(file_get_contents($file->getRealPath()) ?: '');
            Storage::disk($disk)->put($path, $content);
            $width = null;
            $height = null;
        } else {
            Storage::disk($disk)->putFileAs(
                dirname($path),
                $file,
                basename($path),
            );

            [$width, $height] = $this->generateThumbnail($disk, $path, $mime, $ulid, $ext, $companyId, $year, $month);
        }

        return CmsMedia::create([
            'company_id' => $companyId,
            'uploaded_by' => $uploader->id,
            'filename' => $file->getClientOriginalName(),
            'path' => $path,
            'disk' => $disk,
            'mime_type' => $mime,
            'size_bytes' => $file->getSize(),
            'width' => $width,
            'height' => $height,
        ]);
    }

    public function update(CmsMedia $media, UpdateMediaData $data): CmsMedia
    {
        $media->update([
            'alt_text' => $data->altText,
            'caption' => $data->caption,
        ]);

        return $media->fresh() ?? $media;
    }

    public function delete(CmsMedia $media): void
    {
        $media->delete();
    }

    /** @return LengthAwarePaginator<int, CmsMedia> */
    public function paginate(string $companyId, ?string $search, int $perPage = 40): LengthAwarePaginator
    {
        return CmsMedia::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->when($search, fn ($q) => $q->where('filename', 'like', '%' . $search . '%'))
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    /** @return array{int|null, int|null} */
    private function generateThumbnail(
        string $disk,
        string $originalPath,
        string $mime,
        string $ulid,
        string $ext,
        string $companyId,
        string $year,
        string $month,
    ): array {
        if ($mime === 'image/gif') {
            // Skip thumbnail for GIF to avoid frame complexity
            return [null, null];
        }

        try {
            $manager = new ImageManager(new Driver);
            $content = Storage::disk($disk)->get($originalPath);

            if ($content === null) {
                return [null, null];
            }

            $image = $manager->read($content);
            $width = $image->width();
            $height = $image->height();

            // Only generate thumb if larger than thumb size
            if ($width > self::THUMB_SIZE || $height > self::THUMB_SIZE) {
                $thumb = $manager->read($content)->scaleDown(self::THUMB_SIZE, self::THUMB_SIZE);
                $thumbPath = "media/{$companyId}/{$year}/{$month}/{$ulid}_thumb.{$ext}";
                Storage::disk($disk)->put($thumbPath, $thumb->toJpeg()->toString());
            }

            return [$width, $height];
        } catch (\Throwable) {
            return [null, null];
        }
    }
}
