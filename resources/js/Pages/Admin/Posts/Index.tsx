import { Head, router } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import type { CmsPost } from '@/types/models';

interface PostRow {
    id: string;
    title: string;
    slug: string;
    status: 'draft' | 'published' | 'scheduled';
    published_at: string | null;
    created_at: string;
    author: { id: string; name: string } | null;
}

interface Props {
    posts: PostRow[];
}

export default function PostsIndex({ posts }: Props) {
    function handleDelete(post: PostRow) {
        if (!confirm(`Delete "${post.title}"? This cannot be undone.`)) return;
        router.delete(route('admin.posts.destroy', post.id));
    }

    function handlePublish(post: PostRow) {
        router.post(route('admin.posts.publish', post.id));
    }

    function handleUnpublish(post: PostRow) {
        router.post(route('admin.posts.unpublish', post.id));
    }

    const statusBadge = (status: PostRow['status']) => {
        if (status === 'published') return 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-400';
        if (status === 'scheduled') return 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-400';
        return 'bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400';
    };

    return (
        <AdminLayout title="Posts">
            <Head title="Posts" />

            <div className="flex items-center justify-between mb-6">
                <h1 className="text-xl font-semibold text-zinc-900 dark:text-zinc-100">Posts</h1>
                <a
                    href={route('admin.posts.create')}
                    className="inline-flex items-center gap-1.5 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 transition-colors"
                >
                    <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 4v16m8-8H4" />
                    </svg>
                    New Post
                </a>
            </div>

            {posts.length === 0 ? (
                <div className="flex flex-col items-center justify-center rounded-xl border-2 border-dashed border-zinc-200 dark:border-zinc-700 py-16 text-center">
                    <svg className="w-10 h-10 text-zinc-300 dark:text-zinc-600 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z" />
                    </svg>
                    <p className="text-sm text-zinc-500 dark:text-zinc-400">No posts yet.</p>
                    <a href={route('admin.posts.create')} className="mt-3 text-sm text-indigo-600 hover:underline">
                        Write your first post
                    </a>
                </div>
            ) : (
                <div className="overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700">
                    <table className="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                        <thead className="bg-zinc-50 dark:bg-zinc-800">
                            <tr>
                                <th className="px-4 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Title</th>
                                <th className="px-4 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Author</th>
                                <th className="px-4 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Status</th>
                                <th className="px-4 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Date</th>
                                <th className="px-4 py-3 text-right text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-zinc-200 dark:divide-zinc-700 bg-white dark:bg-zinc-900">
                            {posts.map((post) => (
                                <tr key={post.id} className="hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition-colors">
                                    <td className="px-4 py-3">
                                        <span className="text-sm font-medium text-zinc-900 dark:text-zinc-100">{post.title}</span>
                                        <code className="ml-2 text-xs text-zinc-400">/{post.slug}</code>
                                    </td>
                                    <td className="px-4 py-3">
                                        <span className="text-xs text-zinc-500 dark:text-zinc-400">{post.author?.name ?? '—'}</span>
                                    </td>
                                    <td className="px-4 py-3">
                                        <span className={`inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium ${statusBadge(post.status)}`}>
                                            {post.status.charAt(0).toUpperCase() + post.status.slice(1)}
                                        </span>
                                    </td>
                                    <td className="px-4 py-3">
                                        <span className="text-xs text-zinc-500 dark:text-zinc-400">
                                            {post.published_at
                                                ? new Date(post.published_at).toLocaleDateString()
                                                : new Date(post.created_at).toLocaleDateString()}
                                        </span>
                                    </td>
                                    <td className="px-4 py-3 text-right">
                                        <div className="flex items-center justify-end gap-2">
                                            {post.status === 'draft' ? (
                                                <button
                                                    onClick={() => handlePublish(post)}
                                                    className="text-xs text-green-600 hover:text-green-700 dark:text-green-400 font-medium"
                                                >
                                                    Publish
                                                </button>
                                            ) : post.status === 'published' ? (
                                                <button
                                                    onClick={() => handleUnpublish(post)}
                                                    className="text-xs text-amber-600 hover:text-amber-700 dark:text-amber-400 font-medium"
                                                >
                                                    Unpublish
                                                </button>
                                            ) : null}
                                            <a
                                                href={route('admin.posts.edit', post.id)}
                                                className="text-xs text-indigo-600 hover:text-indigo-700 dark:text-indigo-400 font-medium"
                                            >
                                                Edit
                                            </a>
                                            <button
                                                onClick={() => handleDelete(post)}
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
