<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\DTO\UpdateMediaData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreMediaRequest;
use App\Http\Requests\Admin\UpdateMediaRequest;
use App\Models\CmsMedia;
use App\Services\MediaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

final class MediaController extends Controller
{
    public function __construct(private readonly MediaService $mediaService) {}

    public function index(Request $request): Response
    {
        abort_unless((bool) auth('cms')->user()?->can('viewAny', CmsMedia::class), 403);

        $company = app('current_company');
        $media = $this->mediaService->paginate(
            companyId: $company->id,
            search: $request->string('search')->toString() ?: null,
        );

        return Inertia::render('Admin/Media/Index', [
            'media' => $media->through(fn (CmsMedia $m) => $this->toArray($m)),
            'filters' => ['search' => $request->input('search', '')],
        ]);
    }

    public function store(StoreMediaRequest $request): JsonResponse
    {
        abort_unless((bool) auth('cms')->user()?->can('create', CmsMedia::class), 403);

        try {
            $media = $this->mediaService->upload(
                uploader: auth('cms')->user(),
                file: $request->file('file'),
            );

            return response()->json($this->toArray($media), 201);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function update(UpdateMediaRequest $request, CmsMedia $media): RedirectResponse
    {
        abort_unless((bool) auth('cms')->user()?->can('update', $media), 403);

        $this->mediaService->update($media, UpdateMediaData::fromRequest($request));

        return back()->with(['alert' => 'Media updated.', 'type' => 'success']);
    }

    public function destroy(CmsMedia $media): RedirectResponse
    {
        abort_unless((bool) auth('cms')->user()?->can('delete', $media), 403);

        $this->mediaService->delete($media);

        return redirect()->route('admin.media.index')
            ->with(['alert' => 'Media deleted.', 'type' => 'success']);
    }

    /** @return array<string, mixed> */
    private function toArray(CmsMedia $media): array
    {
        $disk = Storage::disk($media->disk);
        $url = $disk->url($media->path);
        $thumbPath = str_replace('.' . pathinfo($media->path, PATHINFO_EXTENSION), '_thumb.' . pathinfo($media->path, PATHINFO_EXTENSION), $media->path);
        $thumbUrl = $disk->exists($thumbPath) ? $disk->url($thumbPath) : $url;

        return [
            'id' => $media->id,
            'filename' => $media->filename,
            'url' => $url,
            'thumb_url' => $thumbUrl,
            'mime_type' => $media->mime_type,
            'size_bytes' => $media->size_bytes,
            'width' => $media->width,
            'height' => $media->height,
            'alt_text' => $media->alt_text,
            'caption' => $media->caption,
            'created_at' => $media->created_at?->toISOString(),
        ];
    }
}
