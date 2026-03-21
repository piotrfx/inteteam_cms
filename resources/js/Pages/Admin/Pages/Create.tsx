import { Head, router, useForm } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import BlockEditor from '@/Components/BlockEditor/BlockEditor';
import SeoFields from '@/Components/SeoFields';
import type { Block } from '@/types/models';

type PageType = 'home' | 'about' | 'contact' | 'privacy' | 'terms' | 'custom';

const PAGE_TYPES: { value: PageType; label: string }[] = [
    { value: 'home', label: 'Home' },
    { value: 'about', label: 'About' },
    { value: 'contact', label: 'Contact' },
    { value: 'privacy', label: 'Privacy Policy' },
    { value: 'terms', label: 'Terms & Conditions' },
    { value: 'custom', label: 'Custom' },
];

const FIXED_SLUGS: Partial<Record<PageType, string>> = {
    home: 'home',
    about: 'about',
    contact: 'contact',
    privacy: 'privacy-policy',
    terms: 'terms-and-conditions',
};

export default function PagesCreate() {
    const { data, setData, post, processing, errors } = useForm({
        title: '',
        slug: '',
        type: 'custom' as PageType,
        blocks: [] as Block[],
        status: 'draft',
        seo_title: '',
        seo_description: '',
        seo_og_image_path: '',
        seo_canonical_url: '',
        seo_robots: '',
        seo_schema_type: 'WebPage',
    });

    function handleTypeChange(type: PageType) {
        setData('type', type);
        if (FIXED_SLUGS[type]) {
            setData('slug', FIXED_SLUGS[type]!);
        }
    }

    function handleTitleChange(title: string) {
        setData('title', title);
        if (data.type === 'custom' && !data.slug) {
            setData('slug', title.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, ''));
        }
    }

    function submit(status: 'draft' | 'published') {
        post(route('admin.pages.store'), {
            data: { ...data, status },
        } as Parameters<typeof post>[1]);
    }

    const isFixedType = data.type !== 'custom';

    return (
        <AdminLayout title="Create Page">
            <Head title="Create Page" />

            <div className="flex items-center gap-3 mb-6">
                <a
                    href={route('admin.pages.index')}
                    className="text-sm text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300"
                >
                    ← Pages
                </a>
                <span className="text-zinc-300 dark:text-zinc-600">/</span>
                <h1 className="text-xl font-semibold text-zinc-900 dark:text-zinc-100">New Page</h1>
            </div>

            <form onSubmit={(e) => { e.preventDefault(); submit(data.status as 'draft' | 'published'); }}>
                <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    {/* Main content */}
                    <div className="lg:col-span-2 space-y-5">
                        {/* Title */}
                        <div>
                            <label className="block text-sm font-medium text-zinc-700 dark:text-zinc-200 mb-1">Title</label>
                            <input
                                type="text"
                                value={data.title}
                                onChange={(e) => handleTitleChange(e.target.value)}
                                placeholder="Page title"
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
                                    placeholder="page-slug"
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
                        {/* Type */}
                        <div className="rounded-xl border border-zinc-200 dark:border-zinc-700 p-4">
                            <label className="block text-sm font-medium text-zinc-700 dark:text-zinc-200 mb-2">Page Type</label>
                            <select
                                value={data.type}
                                onChange={(e) => handleTypeChange(e.target.value as PageType)}
                                className="w-full rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 text-sm text-zinc-700 dark:text-zinc-200 px-3 py-2"
                            >
                                {PAGE_TYPES.map(({ value, label }) => (
                                    <option key={value} value={value}>{label}</option>
                                ))}
                            </select>
                            {errors.type && <p className="mt-1 text-xs text-red-500">{errors.type}</p>}
                        </div>

                        {/* Actions */}
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
