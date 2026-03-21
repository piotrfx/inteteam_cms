import { Head, router, useForm } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import BlockEditor from '@/Components/BlockEditor/BlockEditor';
import SeoFields from '@/Components/SeoFields';
import type { Block, CmsPage } from '@/types/models';

interface Props {
    page: CmsPage;
}

export default function PagesEdit({ page }: Props) {
    const isFixedType = page.type !== 'custom';

    const { data, setData, processing, errors } = useForm({
        title: page.title,
        slug: page.slug,
        blocks: page.blocks as Block[],
        status: page.status,
        seo_title: page.seo_title ?? '',
        seo_description: page.seo_description ?? '',
        seo_og_image_path: page.seo_og_image_path ?? '',
        seo_canonical_url: page.seo_canonical_url ?? '',
        seo_robots: page.seo_robots ?? '',
        seo_schema_type: page.seo_schema_type ?? 'WebPage',
    });

    function save(status: 'draft' | 'published') {
        router.post(route('admin.pages.update', page.id), {
            ...data,
            status,
            _method: 'PUT',
        });
    }

    function handlePublish() {
        router.post(route('admin.pages.publish', page.id));
    }

    function handleUnpublish() {
        router.post(route('admin.pages.unpublish', page.id));
    }

    function handleDelete() {
        if (!confirm(`Delete "${page.title}"? This cannot be undone.`)) return;
        router.delete(route('admin.pages.destroy', page.id));
    }

    return (
        <AdminLayout title={`Edit — ${page.title}`}>
            <Head title={`Edit — ${page.title}`} />

            <div className="flex items-center gap-3 mb-6">
                <a
                    href={route('admin.pages.index')}
                    className="text-sm text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300"
                >
                    ← Pages
                </a>
                <span className="text-zinc-300 dark:text-zinc-600">/</span>
                <h1 className="text-xl font-semibold text-zinc-900 dark:text-zinc-100 truncate">{page.title}</h1>
            </div>

            <form onSubmit={(e) => { e.preventDefault(); save(data.status as 'draft' | 'published'); }}>
                <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    {/* Main content */}
                    <div className="lg:col-span-2 space-y-5">
                        {/* Title */}
                        <div>
                            <label className="block text-sm font-medium text-zinc-700 dark:text-zinc-200 mb-1">Title</label>
                            <input
                                type="text"
                                value={data.title}
                                onChange={(e) => setData('title', e.target.value)}
                                className="w-full rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 text-zinc-900 dark:text-zinc-100 px-3 py-2 text-sm placeholder-zinc-400 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                            />
                            {errors.title && <p className="mt-1 text-xs text-red-500">{errors.title}</p>}
                        </div>

                        {/* Slug */}
                        <div>
                            <label className="block text-sm font-medium text-zinc-700 dark:text-zinc-200 mb-1">Slug</label>
                            <div className="flex items-center rounded-lg border border-zinc-200 dark:border-zinc-700 overflow-hidden bg-white dark:bg-zinc-900">
                                <span className="pl-3 text-sm text-zinc-400">/</span>
                                <input
                                    type="text"
                                    value={data.slug}
                                    onChange={(e) => setData('slug', e.target.value)}
                                    disabled={isFixedType}
                                    className="flex-1 px-2 py-2 text-sm text-zinc-900 dark:text-zinc-100 placeholder-zinc-400 focus:outline-none bg-transparent disabled:text-zinc-400"
                                />
                            </div>
                            {errors.slug && <p className="mt-1 text-xs text-red-500">{errors.slug}</p>}
                        </div>

                        {/* Block editor */}
                        <div>
                            <label className="block text-sm font-medium text-zinc-700 dark:text-zinc-200 mb-2">Content</label>
                            <BlockEditor
                                blocks={data.blocks}
                                onChange={(blocks) => setData('blocks', blocks)}
                            />
                        </div>

                        {/* SEO */}
                        <SeoFields
                            data={{
                                seo_title: data.seo_title,
                                seo_description: data.seo_description,
                                seo_og_image_path: data.seo_og_image_path,
                                seo_canonical_url: data.seo_canonical_url,
                                seo_robots: data.seo_robots,
                                seo_schema_type: data.seo_schema_type,
                            }}
                            errors={errors as Record<string, string>}
                            onChange={(key, value) => setData(key as keyof typeof data, value)}
                        />
                    </div>

                    {/* Sidebar */}
                    <div className="space-y-4">
                        {/* Page info */}
                        <div className="rounded-xl border border-zinc-200 dark:border-zinc-700 p-4 space-y-2">
                            <div className="flex items-center justify-between">
                                <span className="text-xs text-zinc-500 dark:text-zinc-400">Status</span>
                                <span className={`inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium ${
                                    page.status === 'published'
                                        ? 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-400'
                                        : 'bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400'
                                }`}>
                                    {page.status === 'published' ? 'Published' : 'Draft'}
                                </span>
                            </div>
                            <div className="flex items-center justify-between">
                                <span className="text-xs text-zinc-500 dark:text-zinc-400">Type</span>
                                <span className="text-xs text-zinc-700 dark:text-zinc-200 capitalize">{page.type}</span>
                            </div>
                            {page.published_at && (
                                <div className="flex items-center justify-between">
                                    <span className="text-xs text-zinc-500 dark:text-zinc-400">Published</span>
                                    <span className="text-xs text-zinc-700 dark:text-zinc-200">
                                        {new Date(page.published_at).toLocaleDateString()}
                                    </span>
                                </div>
                            )}
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
                            {page.status === 'draft' ? (
                                <button
                                    type="button"
                                    onClick={handlePublish}
                                    disabled={processing}
                                    className="w-full rounded-lg border border-green-500 text-green-600 dark:text-green-400 px-4 py-2 text-sm font-medium hover:bg-green-50 dark:hover:bg-green-900/20 transition-colors disabled:opacity-60"
                                >
                                    Publish
                                </button>
                            ) : (
                                <button
                                    type="button"
                                    onClick={handleUnpublish}
                                    disabled={processing}
                                    className="w-full rounded-lg border border-amber-500 text-amber-600 dark:text-amber-400 px-4 py-2 text-sm font-medium hover:bg-amber-50 dark:hover:bg-amber-900/20 transition-colors disabled:opacity-60"
                                >
                                    Unpublish
                                </button>
                            )}
                        </div>

                        {/* Danger */}
                        <div className="rounded-xl border border-red-200 dark:border-red-900/50 p-4">
                            <p className="text-xs font-medium text-red-500 mb-3">Danger Zone</p>
                            <button
                                type="button"
                                onClick={handleDelete}
                                className="w-full rounded-lg border border-red-300 dark:border-red-700 text-red-500 px-4 py-2 text-sm font-medium hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors"
                            >
                                Delete Page
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </AdminLayout>
    );
}
