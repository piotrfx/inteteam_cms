<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\DTO\CreatePostData;
use App\DTO\UpdatePostData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StorePostRequest;
use App\Http\Requests\Admin\UpdatePostRequest;
use App\Models\CmsPost;
use App\Services\PostService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

final class PostController extends Controller
{
    public function __construct(private readonly PostService $postService) {}

    public function index(): Response
    {
        abort_unless(auth('cms')->user()?->can('viewAny', CmsPost::class), 403);

        $posts = CmsPost::with('author:id,name')
            ->orderByDesc('created_at')
            ->get(['id', 'author_id', 'title', 'slug', 'status', 'published_at', 'created_at']);

        return Inertia::render('Admin/Posts/Index', [
            'posts' => $posts,
        ]);
    }

    public function create(): Response
    {
        abort_unless(auth('cms')->user()?->can('create', CmsPost::class), 403);

        return Inertia::render('Admin/Posts/Create');
    }

    public function store(StorePostRequest $request): RedirectResponse
    {
        abort_unless(auth('cms')->user()?->can('create', CmsPost::class), 403);

        $post = $this->postService->create(
            author: auth('cms')->user(),
            data: CreatePostData::fromRequest($request),
        );

        return redirect()->route('admin.posts.edit', $post->id)
            ->with(['alert' => 'Post created.', 'type' => 'success']);
    }

    public function edit(CmsPost $post): Response
    {
        abort_unless(auth('cms')->user()?->can('update', $post), 403);

        return Inertia::render('Admin/Posts/Edit', [
            'post' => $post->load('author:id,name'),
        ]);
    }

    public function update(UpdatePostRequest $request, CmsPost $post): RedirectResponse
    {
        abort_unless(auth('cms')->user()?->can('update', $post), 403);

        $this->postService->update($post, UpdatePostData::fromRequest($request));

        return back()->with(['alert' => 'Post saved.', 'type' => 'success']);
    }

    public function destroy(CmsPost $post): RedirectResponse
    {
        abort_unless(auth('cms')->user()?->can('delete', $post), 403);

        $this->postService->delete($post);

        return redirect()->route('admin.posts.index')
            ->with(['alert' => 'Post deleted.', 'type' => 'success']);
    }

    public function publish(CmsPost $post): RedirectResponse
    {
        abort_unless(auth('cms')->user()?->can('publish', $post), 403);

        $this->postService->publish($post);

        return back()->with(['alert' => 'Post published.', 'type' => 'success']);
    }

    public function unpublish(CmsPost $post): RedirectResponse
    {
        abort_unless(auth('cms')->user()?->can('publish', $post), 403);

        $this->postService->unpublish($post);

        return back()->with(['alert' => 'Post unpublished.', 'type' => 'success']);
    }
}
