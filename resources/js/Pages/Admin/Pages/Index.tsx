import { Head, router } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import type { CmsPage } from '@/types/models';

interface Props {
    pages: CmsPage[];
}

const TYPE_LABELS: Record<string, string> = {
    home: 'Home',
    about: 'About',
    contact: 'Contact',
    privacy: 'Privacy Policy',
    terms: 'Terms',
    custom: 'Custom',
};

export default function PagesIndex({ pages }: Props) {
    function handleDelete(page: CmsPage) {
        if (!confirm(`Delete "${page.title}"? This cannot be undone.`)) return;
        router.delete(route('admin.pages.destroy', page.id));
    }

    function handlePublish(page: CmsPage) {
        router.post(route('admin.pages.publish', page.id));
    }

    function handleUnpublish(page: CmsPage) {
        router.post(route('admin.pages.unpublish', page.id));
    }

    return (
        <AdminLayout title="Pages">
            <Head title="Pages" />

            <div className="flex items-center justify-between mb-6">
                <h1 className="text-xl font-semibold text-zinc-900 dark:text-zinc-100">Pages</h1>
                <a
                    href={route('admin.pages.create')}
                    className="inline-flex items-center gap-1.5 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 transition-colors"
                >
                    <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 4v16m8-8H4" />
                    </svg>
                    New Page
                </a>
            </div>

            {pages.length === 0 ? (
                <div className="flex flex-col items-center justify-center rounded-xl border-2 border-dashed border-zinc-200 dark:border-zinc-700 py-16 text-center">
                    <svg className="w-10 h-10 text-zinc-300 dark:text-zinc-600 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <p className="text-sm text-zinc-500 dark:text-zinc-400">No pages yet.</p>
                    <a href={route('admin.pages.create')} className="mt-3 text-sm text-indigo-600 hover:underline">
                        Create your first page
                    </a>
                </div>
            ) : (
                <div className="overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700">
                    <table className="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                        <thead className="bg-zinc-50 dark:bg-zinc-800">
                            <tr>
                                <th className="px-4 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Title</th>
                                <th className="px-4 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Type</th>
                                <th className="px-4 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Slug</th>
                                <th className="px-4 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Status</th>
                                <th className="px-4 py-3 text-right text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-zinc-200 dark:divide-zinc-700 bg-white dark:bg-zinc-900">
                            {pages.map((page) => (
                                <tr key={page.id} className="hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition-colors">
                                    <td className="px-4 py-3">
                                        <span className="text-sm font-medium text-zinc-900 dark:text-zinc-100">{page.title}</span>
                                    </td>
                                    <td className="px-4 py-3">
                                        <span className="text-xs text-zinc-500 dark:text-zinc-400">{TYPE_LABELS[page.type] ?? page.type}</span>
                                    </td>
                                    <td className="px-4 py-3">
                                        <code className="text-xs text-zinc-500 dark:text-zinc-400">/{page.slug}</code>
                                    </td>
                                    <td className="px-4 py-3">
                                        <span className={`inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium ${
                                            page.status === 'published'
                                                ? 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-400'
                                                : 'bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400'
                                        }`}>
                                            {page.status === 'published' ? 'Published' : 'Draft'}
                                        </span>
                                    </td>
                                    <td className="px-4 py-3 text-right">
                                        <div className="flex items-center justify-end gap-2">
                                            {page.status === 'draft' ? (
                                                <button
                                                    onClick={() => handlePublish(page)}
                                                    className="text-xs text-green-600 hover:text-green-700 dark:text-green-400 font-medium"
                                                >
                                                    Publish
                                                </button>
                                            ) : (
                                                <button
                                                    onClick={() => handleUnpublish(page)}
                                                    className="text-xs text-amber-600 hover:text-amber-700 dark:text-amber-400 font-medium"
                                                >
                                                    Unpublish
                                                </button>
                                            )}
                                            <a
                                                href={route('admin.pages.edit', page.id)}
                                                className="text-xs text-indigo-600 hover:text-indigo-700 dark:text-indigo-400 font-medium"
                                            >
                                                Edit
                                            </a>
                                            <button
                                                onClick={() => handleDelete(page)}
                                                className="text-xs text-red-500 hover:text-red-600 dark:text-red-400 font-medium"
                                            >
                                                Delete
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            )}
        </AdminLayout>
    );
}
