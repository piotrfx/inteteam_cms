import { Head, useForm, router } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import { useState } from 'react';

interface Token {
    id: string;
    name: string;
    permissions: string[];
    last_used_at: string | null;
    expires_at: string | null;
    created_at: string | null;
    is_valid: boolean;
}

interface Props {
    tokens: Token[];
    mcp_endpoint: string;
    new_token: string | null;
}

const inputCls = 'w-full rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 text-sm text-zinc-900 dark:text-zinc-100 px-3 py-2 placeholder-zinc-400 focus:outline-none focus:ring-1 focus:ring-indigo-500';
const badgeCls = (active: boolean) =>
    `px-2 py-0.5 rounded text-xs font-medium ${active ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' : 'bg-zinc-100 text-zinc-400 dark:bg-zinc-700'}`;

function permLabel(p: string) {
    return { read: 'Read', write: 'Write', publish: 'Publish' }[p] ?? p;
}

export default function AiIntegration({ tokens, mcp_endpoint, new_token }: Props) {
    const { data, setData, post, processing, errors, reset } = useForm({
        name:         '',
        permissions:  ['read'] as string[],
        expires_days: '',
    });

    const [copied, setCopied] = useState(false);

    function togglePerm(p: string) {
        setData('permissions', data.permissions.includes(p)
            ? data.permissions.filter(x => x !== p)
            : [...data.permissions, p]);
    }

    function submit(e: React.FormEvent) {
        e.preventDefault();
        post(route('admin.settings.ai.tokens.store'), { onSuccess: () => reset() });
    }

    function revoke(id: string) {
        if (! confirm('Revoke this token? The action cannot be undone.')) return;
        router.post(route('admin.settings.ai.tokens.revoke', id), {}, { preserveScroll: true });
    }

    function copyToken() {
        if (new_token) {
            navigator.clipboard.writeText(new_token);
            setCopied(true);
            setTimeout(() => setCopied(false), 2000);
        }
    }

    return (
        <AdminLayout>
            <Head title="AI Integration" />

            <div className="max-w-2xl space-y-6">
                <div>
                    <h1 className="text-xl font-semibold text-zinc-900 dark:text-zinc-100">AI Integration</h1>
                    <p className="mt-1 text-sm text-zinc-500">
                        MCP tokens let Claude edit your website content via AI tools.
                    </p>
                </div>

                {/* Show new token once */}
                {new_token && (
                    <div className="rounded-xl border border-amber-200 bg-amber-50 dark:border-amber-800 dark:bg-amber-900/20 p-4 space-y-2">
                        <p className="text-sm font-semibold text-amber-800 dark:text-amber-300">
                            Copy your token now — it will not be shown again.
                        </p>
                        <div className="flex items-center gap-2">
                            <code className="flex-1 rounded bg-white dark:bg-zinc-900 border border-amber-200 dark:border-amber-800 px-3 py-2 text-xs font-mono text-zinc-900 dark:text-zinc-100 break-all">
                                {new_token}
                            </code>
                            <button
                                type="button"
                                onClick={copyToken}
                                className="shrink-0 rounded-lg border border-amber-300 dark:border-amber-700 px-3 py-2 text-xs font-medium text-amber-700 dark:text-amber-300 hover:bg-amber-100 dark:hover:bg-amber-900/40"
                            >
                                {copied ? 'Copied!' : 'Copy'}
                            </button>
                        </div>
                    </div>
                )}

                {/* Endpoint */}
                <div className="rounded-xl border border-zinc-200 dark:border-zinc-700 overflow-hidden">
                    <div className="px-4 py-3 bg-zinc-50 dark:bg-zinc-800 border-b border-zinc-200 dark:border-zinc-700">
                        <h2 className="text-sm font-semibold text-zinc-700 dark:text-zinc-200">Endpoint</h2>
                    </div>
                    <div className="p-4">
                        <p className="text-xs text-zinc-500 mb-1">Use this URL when configuring the MCP server in Claude settings:</p>
                        <code className="block rounded bg-zinc-50 dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 px-3 py-2 text-sm font-mono text-zinc-900 dark:text-zinc-100">
                            {mcp_endpoint}
                        </code>
                    </div>
                </div>

                {/* Create token */}
                <form onSubmit={submit} className="rounded-xl border border-zinc-200 dark:border-zinc-700 overflow-hidden">
                    <div className="px-4 py-3 bg-zinc-50 dark:bg-zinc-800 border-b border-zinc-200 dark:border-zinc-700">
                        <h2 className="text-sm font-semibold text-zinc-700 dark:text-zinc-200">Create Token</h2>
                    </div>
                    <div className="p-4 space-y-4">
                        <div>
                            <label className="block text-sm font-medium text-zinc-700 dark:text-zinc-200 mb-1">Name</label>
                            <input
                                type="text"
                                className={inputCls}
                                value={data.name}
                                onChange={e => setData('name', e.target.value)}
                                placeholder="e.g. Claude.ai assistant"
                                required
                            />
                            {errors.name && <p className="mt-1 text-xs text-red-500">{errors.name}</p>}
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-zinc-700 dark:text-zinc-200 mb-2">Permissions</label>
                            <div className="flex gap-3">
                                {(['read', 'write', 'publish'] as const).map(p => (
                                    <label key={p} className="flex items-center gap-2 cursor-pointer">
                                        <input
                                            type="checkbox"
                                            checked={data.permissions.includes(p)}
                                            onChange={() => togglePerm(p)}
                                            className="rounded border-zinc-300 dark:border-zinc-600"
                                        />
                                        <span className="text-sm text-zinc-700 dark:text-zinc-200 capitalize">{p}</span>
                                    </label>
                                ))}
                            </div>
                            <p className="mt-1 text-xs text-zinc-400">Write required to stage edits. Publish required to go live (not recommended for AI).</p>
                            {errors.permissions && <p className="mt-1 text-xs text-red-500">{errors.permissions}</p>}
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-zinc-700 dark:text-zinc-200 mb-1">
                                Expires in (days)
                                <span className="ml-1 font-normal text-zinc-400">— leave blank for no expiry</span>
                            </label>
                            <input
                                type="number"
                                className={inputCls}
                                value={data.expires_days}
                                onChange={e => setData('expires_days', e.target.value)}
                                min={1}
                                max={365}
                                placeholder="e.g. 90"
                            />
                        </div>
                    </div>
                    <div className="px-4 py-3 bg-zinc-50 dark:bg-zinc-800 border-t border-zinc-200 dark:border-zinc-700">
                        <button
                            type="submit"
                            disabled={processing || data.permissions.length === 0}
                            className="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50"
                        >
                            {processing ? 'Creating…' : 'Create Token'}
                        </button>
                    </div>
                </form>

                {/* Token list */}
                {tokens.length > 0 && (
                    <div className="rounded-xl border border-zinc-200 dark:border-zinc-700 overflow-hidden">
                        <div className="px-4 py-3 bg-zinc-50 dark:bg-zinc-800 border-b border-zinc-200 dark:border-zinc-700">
                            <h2 className="text-sm font-semibold text-zinc-700 dark:text-zinc-200">Tokens</h2>
                        </div>
                        <ul className="divide-y divide-zinc-100 dark:divide-zinc-800">
                            {tokens.map(t => (
                                <li key={t.id} className="px-4 py-3 flex items-start gap-3">
                                    <div className="flex-1 min-w-0">
                                        <div className="flex items-center gap-2 flex-wrap">
                                            <p className="text-sm font-medium text-zinc-800 dark:text-zinc-100">{t.name}</p>
                                            <span className={badgeCls(t.is_valid)}>{t.is_valid ? 'Active' : 'Revoked / Expired'}</span>
                                        </div>
                                        <div className="mt-1 flex flex-wrap gap-1">
                                            {t.permissions.map(p => (
                                                <span key={p} className="px-1.5 py-0.5 rounded bg-indigo-50 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300 text-xs font-medium">
                                                    {permLabel(p)}
                                                </span>
                                            ))}
                                        </div>
                                        <p className="mt-1 text-xs text-zinc-400">
                                            Created {t.created_at ? new Date(t.created_at).toLocaleDateString() : '—'}
                                            {t.last_used_at && ` · Last used ${new Date(t.last_used_at).toLocaleDateString()}`}
                                            {t.expires_at && ` · Expires ${new Date(t.expires_at).toLocaleDateString()}`}
                                        </p>
                                    </div>
                                    {t.is_valid && (
                                        <button
                                            type="button"
                                            onClick={() => revoke(t.id)}
                                            className="shrink-0 text-xs text-red-500 hover:text-red-700 font-medium"
                                        >
                                            Revoke
                                        </button>
                                    )}
                                </li>
                            ))}
                        </ul>
                    </div>
                )}
            </div>
        </AdminLayout>
    );
}
