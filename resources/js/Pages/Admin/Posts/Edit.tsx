import { Head, router, useForm } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import BlockEditor from '@/Components/BlockEditor/BlockEditor';
import SeoFields from '@/Components/SeoFields';
import type { Block, CmsPost } from '@/types/models';

interface Props {
    post: CmsPost;
}

export default function PostsEdit({ post }: Props) {
    const { data, setData, processing, errors } = useForm({
        title: post.title,
        slug: post.slug,
        excerpt: post.excerpt ?? '',
        blocks: post.blocks as Block[],
        status: post.status,
        featured_image_path: post.featured_image_path ?? '',
        seo_title: post.seo_title ?? '',
        seo_description: post.seo_description ?? '',
        seo_og_image_path: post.seo_og_image_path ?? '',
        seo_canonical_url: '',
        seo_robots: post.seo_robots ?? '',
    });

    function save() {
        router.post(route('admin.posts.update', post.id), {
            ...data,
            _method: 'PUT',
        });
    }

    function handlePublish() {
        router.post(route('admin.posts.publish', post.id));
    }

    function handleUnpublish() {
        router.post(route('admin.posts.unpublish', post.id));
    }

    function handleDelete() {
        if (!confirm(`Delete "${post.title}"? This cannot be undone.`)) return;
        router.delete(route('admin.posts.destroy', post.id));
    }

    const statusBadge = () => {
        if (post.status === 'published') return 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-400';
        if (post.status === 'scheduled') return 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-400';
        return 'bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400';
    };

    return (
        <AdminLayout title={`Edit — ${post.title}`}>
            <Head title={`Edit — ${post.title}`} />

            <div className="flex items-center gap-3 mb-6">
                <a
                    href={route('admin.posts.index')}
                    className="text-sm text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300"
                >
                    ← Posts
                </a>
                <span className="text-zinc-300 dark:text-zinc-600">/</span>
                <h1 className="text-xl font-semibold text-zinc-900 dark:text-zinc-100 truncate">{post.title}</h1>
            </div>

            <form onSubmit={(e) => { e.preventDefault(); save(); }}>
                <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    {/* Main content */}
                    <div className="lg:col-span-2 space-y-5">
                        <div>
                            <label className="block text-sm font-medium text-zinc-700 dark:text-zinc-200 mb-1">Title</label>
                            <input
                                type="text"
                                value={data.title}
                                onChange={(e) => setData('title', e.target.value)}
                                className="w-full rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 text-zinc-900 dark:text-zinc-100 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                            />
                            {errors.title && <p className="mt-1 text-xs text-red-500">{errors.title}</p>}
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-zinc-700 dark:text-zinc-200 mb-1">Slug</label>
                            <div className="flex items-center rounded-lg border border-zinc-200 dark:border-zinc-700 overflow-hidden bg-white dark:bg-zinc-900">
                                <span className="pl-3 text-sm text-zinc-400">/blog/</span>
                                <input
                                    type="text"
                                    value={data.slug}
                                    onChange={(e) => setData('slug', e.target.value)}
                                    className="flex-1 px-2 py-2 text-sm text-zinc-900 dark:text-zinc-100 focus:outline-none bg-transparent"
                                />
                            </div>
                            {errors.slug && <p className="mt-1 text-xs text-red-500">{errors.slug}</p>}
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-zinc-700 dark:text-zinc-200 mb-1">Excerpt</label>
                            <textarea
                                value={data.excerpt}
                                onChange={(e) => setData('excerpt', e.target.value)}
                                rows={3}
                                className="w-full rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 text-zinc-900 dark:text-zinc-100 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 resize-none"
                            />
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-zinc-700 dark:text-zinc-200 mb-2">Content</label>
                            <BlockEditor
                                blocks={data.blocks}
                                onChange={(blocks) => setData('blocks', blocks)}
                            />
                        </div>

                        <SeoFields
                            data={{
                                seo_title: data.seo_title,
                                seo_description: data.seo_description,
                                seo_og_image_path: data.seo_og_image_path,
                                seo_canonical_url: data.seo_canonical_url,
                                seo_robots: data.seo_robots,
                                seo_schema_type: 'WebPage',
                            }}
                            errors={errors as Record<string, string>}
                            onChange={(key, value) => {
                                if (key !== 'seo_schema_type') setData(key as keyof typeof data, value);
                            }}
                        />
                    </div>

                    {/* Sidebar */}
                    <div className="space-y-4">
                        {/* Post info */}
                        <div className="rounded-xl border border-zinc-200 dark:border-zinc-700 p-4 space-y-2">
                            <div className="flex items-center justify-between">
                                <span className="text-xs text-zinc-500 dark:text-zinc-400">Status</span>
                                <span className={`inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium ${statusBadge()}`}>
                                    {post.status.charAt(0).toUpperCase() + post.status.slice(1)}
                                </span>
                            </div>
                            {post.author && (
                                <div className="flex items-center justify-between">
                                    <span className="text-xs text-zinc-500 dark:text-zinc-400">Author</span>
                                    <span className="text-xs text-zinc-700 dark:text-zinc-200">{post.author.name}</span>
                                </div>
                            )}
                            {post.published_at && (
                                <div className="flex items-center justify-between">
                                    <span className="text-xs text-zinc-500 dark:text-zinc-400">Published</span>
                                    <span className="text-xs text-zinc-700 dark:text-zinc-200">
                                        {new Date(post.published_at).toLocaleDateString()}
                                    </span>
                                </div>
                            )}
                        </div>

                        {/* Featured image */}
                        <div className="rounded-xl border border-zinc-200 dark:border-zinc-700 p-4">
                            <label className="block text-sm font-medium text-zinc-700 dark:text-zinc-200 mb-1">Featured Image</label>
                            <input
                                type="text"
                                value={data.featured_image_path}
                                onChange={(e) => setData('featured_image_path', e.target.value)}
                                placeholder="/storage/media/…"
                                className="w-full rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 text-sm text-zinc-900 dark:text-zinc-100 px-3 py-2 placeholder-zinc-400 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                            />
                        </div>

                        {/* Actions */}
                        <div className="rounded-xl border border-zinc-200 dark:border-zinc-700 p-4 space-y-3">
                            <p className="text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Save</p>
                            <button
                                type="submit"
                                disabled={processing}
                                className="w-full rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 transition-colors disabled:opacity-60"
                            >
                                Save Changes
                            </button>
                            {post.status === 'draft' ? (
                                <button
                                    type="button"
                                    onClick={handlePublish}
                                    disabled={processing}
                                    className="w-full rounded-lg border border-green-500 text-green-600 dark:text-green-400 px-4 py-2 text-sm font-medium hover:bg-green-50 dark:hover:bg-green-900/20 transition-colors disabled:opacity-60"
                                >
                                    Publish
                                </button>
                            ) : post.status === 'published' ? (
                                <button
                                    type="button"
                                    onClick={handleUnpublish}
                                    disabled={processing}
                                    className="w-full rounded-lg border border-amber-500 text-amber-600 dark:text-amber-400 px-4 py-2 text-sm font-medium hover:bg-amber-50 dark:hover:bg-amber-900/20 transition-colors disabled:opacity-60"
                                >
                                    Unpublish
                                </button>
                            ) : null}
                        </div>

                        {/* Staged revision panel */}
                        {post.staged_revision_id && (
                            <div className="rounded-xl border border-indigo-300 dark:border-indigo-700 p-4 space-y-2 bg-indigo-50 dark:bg-indigo-900/20">
                                <p className="text-xs font-semibold text-indigo-700 dark:text-indigo-300">&#9889; Staged Changes</p>
                                <p className="text-xs text-indigo-600 dark:text-indigo-400">There are unpublished changes staged for this post.</p>
                                <a
                                    href={route('admin.posts.staged.preview', post.id)}
                                    className="block w-full text-center rounded-lg bg-indigo-600 text-white px-4 py-2 text-sm font-medium hover:bg-indigo-700 transition-colors"
                                >
                                    Preview
                                </a>
                                <button
                                    type="button"
                                    onClick={() => router.post(route('admin.posts.staged.publish', post.id))}
                                    className="w-full rounded-lg border border-green-500 text-green-600 dark:text-green-400 px-4 py-2 text-sm font-medium hover:bg-green-50 dark:hover:bg-green-900/20 transition-colors"
                                >
                                    Publish Staged
                                </button>
                                <button
                                    type="button"
                                    onClick={() => router.post(route('admin.posts.staged.discard', post.id))}
                                    className="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 text-zinc-500 px-4 py-2 text-sm font-medium hover:bg-zinc-50 dark:hover:bg-zinc-800 transition-colors"
                                >
                                    Discard Staged
                                </button>
                            </div>
                        )}

                        {/* Revision history link */}
                        <div className="text-center">
                            <a
                                href={route('admin.posts.revisions', post.id)}
                                className="text-xs text-zinc-400 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors"
                            >
                                View revision history →
                            </a>
                        </div>

                        {/* Danger */}
                        <div className="rounded-xl border border-red-200 dark:border-red-900/50 p-4">
                            <p className="text-xs font-medium text-red-500 mb-3">Danger Zone</p>
                            <button
                                type="button"
                                onClick={handleDelete}
                                className="w-full rounded-lg border border-red-300 dark:border-red-700 text-red-500 px-4 py-2 text-sm font-medium hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors"
                            >
                                Delete Post
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </AdminLayout>
    );
}
