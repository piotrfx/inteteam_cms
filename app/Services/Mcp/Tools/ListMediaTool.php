<?php

declare(strict_types=1);

namespace App\Services\Mcp\Tools;

use App\Models\CmsMcpToken;
use App\Models\CmsMedia;
use App\Services\Mcp\McpTool;
use Illuminate\Support\Facades\Storage;

final class ListMediaTool implements McpTool
{
    public function name(): string
    {
        return 'list_media';
    }

    public function description(): string
    {
        return 'List media files uploaded to this website. Returns id, filename, path (public URL), mime type, dimensions, and alt text.';
    }

    public function inputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'mime_prefix' => [
                    'type' => 'string',
                    'description' => 'Filter by MIME type prefix, e.g. "image/" for images only.',
                ],
                'limit' => [
                    'type' => 'integer',
                    'minimum' => 1,
                    'maximum' => 100,
                    'description' => 'Max results. Defaults to 50.',
                ],
            ],
        ];
    }

    public function execute(array $input, CmsMcpToken $token): array
    {
        $query = CmsMedia::query()->orderByDesc('created_at');

        if (isset($input['mime_prefix']) && is_string($input['mime_prefix'])) {
            $query->where('mime_type', 'like', $input['mime_prefix'] . '%');
        }

        $limit = is_int($input['limit'] ?? null) ? min($input['limit'], 100) : 50;
        $media = $query->limit($limit)->get();

        return [
            'media' => $media->map(fn (CmsMedia $m) => [
                'id' => $m->id,
                'filename' => $m->filename,
                'url' => Storage::url($m->path),
                'mime_type' => $m->mime_type,
                'size_bytes' => $m->size_bytes,
                'width' => $m->width,
                'height' => $m->height,
                'alt_text' => $m->alt_text,
                'caption' => $m->caption,
            ])->values()->all(),
        ];
    }
}
