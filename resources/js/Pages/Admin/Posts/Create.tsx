import { Head, useForm } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import BlockEditor from '@/Components/BlockEditor/BlockEditor';
import SeoFields from '@/Components/SeoFields';
import type { Block } from '@/types/models';

export default function PostsCreate() {
    const { data, setData, post, processing, errors } = useForm({
        title: '',
        slug: '',
        excerpt: '',
        blocks: [] as Block[],
        status: 'draft',
        featured_image_path: '',
        seo_title: '',
        seo_description: '',
        seo_og_image_path: '',
        seo_canonical_url: '',
        seo_robots: '',
    });

    function handleTitleChange(title: string) {
        setData('title', title);
        if (!data.slug) {
            setData('slug', title.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, ''));
        }
    }

    function submit(status: 'draft' | 'published') {
        post(route('admin.posts.store'), {
            data: { ...data, status },
        } as Parameters<typeof post>[1]);
    }

    return (
        <AdminLayout title="New Post">
            <Head title="New Post" />

            <div className="flex items-center gap-3 mb-6">
                <a
                    href={route('admin.posts.index')}
                    className="text-sm text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300"
                >
                    ← Posts
                </a>
                <span className="text-zinc-300 dark:text-zinc-600">/</span>
                <h1 className="text-xl font-semibold text-zinc-900 dark:text-zinc-100">New Post</h1>
            </div>

            <form onSubmit={(e) => { e.preventDefault(); submit(data.status as 'draft' | 'published'); }}>
                <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    {/* Main content */}
                    <div className="lg:col-span-2 space-y-5">
                        <div>
                            <label className="block text-sm font-medium text-zinc-700 dark:text-zinc-200 mb-1">Title</label>
                            <input
                                type="text"
                                value={data.title}
                                onChange={(e) => handleTitleChange(e.target.value)}
                                placeholder="Post title"
                                className="w-full rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 text-zinc-900 dark:text-zinc-100 px-3 py-2 text-sm placeholder-zinc-400 focus:outline-none focus:ring-2 focus:ring-indigo-500"
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
                                    placeholder="post-slug"
                                    className="flex-1 px-2 py-2 text-sm text-zinc-900 dark:text-zinc-100 placeholder-zinc-400 focus:outline-none bg-transparent"
                                />
                            </div>
                            {errors.slug && <p className="mt-1 text-xs text-red-500">{errors.slug}</p>}
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-zinc-700 dark:text-zinc-200 mb-1">Excerpt</label>
                            <textarea
                                value={data.excerpt}
                                onChange={(e) => setData('excerpt', e.target.value)}
                                placeholder="Short summary (optional)"
                                rows={3}
                                className="w-full rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 text-zinc-900 dark:text-zinc-100 px-3 py-2 text-sm placeholder-zinc-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 resize-none"
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
                        <div className="rounded-xl border border-zinc-200 dark:border-zinc-700 p-4">
                            <label className="block text-sm font-medium text-zinc-700 dark:text-zinc-200 mb-1">Featured Image Path</label>
                            <input
                                type="text"
                                value={data.featured_image_path}
                                onChange={(e) => setData('featured_image_path', e.target.value)}
                                placeholder="/storage/media/…"
                                className="w-full rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 text-sm text-zinc-900 dark:text-zinc-100 px-3 py-2 placeholder-zinc-400 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                            />
                        </div>

                        <div className="rounded-xl border border-zinc-200 dark:border-zinc-700 p-4 space-y-3">
                            <p className="text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Publish</p>
                            <button
                                type="button"
                                onClick={() => submit('published')}
                                disabled={processing}
                                className="w-full rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 transition-colors disabled:opacity-60"
                            >
                                Publish
                            </button>
                            <button
                                type="button"
                                onClick={() => submit('draft')}
                                disabled={processing}
                                className="w-full rounded-lg border border-zinc-200 dark:border-zinc-700 px-4 py-2 text-sm font-medium text-zinc-700 dark:text-zinc-200 hover:bg-zinc-50 dark:hover:bg-zinc-800 transition-colors disabled:opacity-60"
                            >
                                Save Draft
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </AdminLayout>
    );
}
