import { Head, router } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import type { Revision } from '@/types/models';

interface Content {
    id: string;
    title: string;
    type: 'page' | 'post';
}

interface Props {
    content: Content;
    revisions: Revision[];
}

function badge(rev: Revision) {
    if (rev.is_live) return <span className="text-xs bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-400 rounded-full px-2 py-0.5 font-medium">Live</span>;
    if (rev.is_staged) return <span className="text-xs bg-indigo-100 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-400 rounded-full px-2 py-0.5 font-medium">Staged</span>;
    return null;
}

export default function RevisionsIndex({ content, revisions }: Props) {
    const backRoute = content.type === 'page'
        ? route('admin.pages.edit', content.id)
        : route('admin.posts.edit', content.id);

    const restoreRoute = content.type === 'page'
        ? (revId: string) => route('admin.pages.revisions.restore', { page: content.id, revision: revId })
        : (revId: string) => route('admin.posts.revisions.restore', { post: content.id, revision: revId });

    function restore(revId: string) {
        if (!confirm('Restore this revision as a staged draft? The current staged version will be replaced.')) return;
        router.post(restoreRoute(revId));
    }

    return (
        <AdminLayout title="Revision History">
            <Head title="Revision History" />

            <div className="flex items-center gap-3 mb-6">
                <a href={backRoute} className="text-sm text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300">
                    ← {content.title}
                </a>
                <span className="text-zinc-300 dark:text-zinc-600">/</span>
                <h1 className="text-xl font-semibold text-zinc-900 dark:text-zinc-100">Revision History</h1>
            </div>

            <div className="max-w-2xl space-y-3">
                {revisions.length === 0 && (
                    <p className="text-sm text-zinc-400 text-center py-12">No revisions yet.</p>
                )}

                {revisions.map((rev) => (
                    <div
                        key={rev.id}
                        className={`rounded-xl border p-4 flex items-start gap-4 ${
                            rev.is_live
                                ? 'border-green-300 dark:border-green-700 bg-green-50 dark:bg-green-900/10'
                                : rev.is_staged
                                    ? 'border-indigo-300 dark:border-indigo-700 bg-indigo-50 dark:bg-indigo-900/10'
                                    : 'border-zinc-200 dark:border-zinc-700'
                        }`}
                    >
                        <div className="flex-1 min-w-0">
                            <div className="flex items-center gap-2 mb-1">
                                {badge(rev)}
                                <span className="text-xs text-zinc-400">
                                    {rev.created_by_type === 'ai_agent' ? 'AI Assistant' : 'Editor'}
                                    {rev.created_by_id ? ` (${rev.created_by_id})` : ''}
                                </span>
                                <span className="text-xs text-zinc-300 dark:text-zinc-600">·</span>
                                <span className="text-xs text-zinc-400">
                                    {new Date(rev.created_at).toLocaleString()}
                                </span>
                            </div>
                            <p className="text-sm text-zinc-700 dark:text-zinc-200 truncate">
                                {rev.summary ?? <span className="text-zinc-400 italic">No summary</span>}
                            </p>
                        </div>

                        {!rev.is_live && !rev.is_staged && (
                            <button
                                type="button"
                                onClick={() => restore(rev.id)}
                                className="shrink-0 text-xs text-indigo-600 dark:text-indigo-400 hover:underline"
                            >
                                Restore as staged
                            </button>
                        )}
                    </div>
                ))}
            </div>
        </AdminLayout>
    );
}
