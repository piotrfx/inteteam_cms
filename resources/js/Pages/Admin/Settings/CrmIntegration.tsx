import { Head, useForm, router } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import { useState } from 'react';

interface CrmSettings {
    crm_base_url: string | null;
    crm_company_id: string | null;
    has_api_key: boolean;
    is_connected: boolean;
}

interface Props {
    crm: CrmSettings;
}

const inputCls = 'w-full rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 text-sm text-zinc-900 dark:text-zinc-100 px-3 py-2 placeholder-zinc-400 focus:outline-none focus:ring-1 focus:ring-indigo-500';

function Field({ label, hint, error, children }: {
    label: string;
    hint?: string;
    error?: string;
    children: React.ReactNode;
}) {
    return (
        <div>
            <label className="block text-sm font-medium text-zinc-700 dark:text-zinc-200 mb-1">{label}</label>
            {hint && <p className="text-xs text-zinc-400 mb-1">{hint}</p>}
            {children}
            {error && <p className="mt-1 text-xs text-red-500">{error}</p>}
        </div>
    );
}

export default function CrmIntegration({ crm }: Props) {
    const { data, setData, post, processing, errors } = useForm({
        crm_base_url:   crm.crm_base_url   ?? '',
        crm_company_id: crm.crm_company_id ?? '',
        crm_api_key:    '',
    });

    const [testStatus, setTestStatus] = useState<{ ok: boolean; message: string } | null>(null);
    const [testing, setTesting] = useState(false);

    function submit(e: React.FormEvent) {
        e.preventDefault();
        post(route('admin.settings.crm.update'));
    }

    function testConnection() {
        setTesting(true);
        setTestStatus(null);
        router.post(
            route('admin.settings.crm.test'),
            {},
            {
                onSuccess: () => setTestStatus({ ok: true, message: 'Connected successfully.' }),
                onError: () => setTestStatus({ ok: false, message: 'Connection failed. Check your credentials.' }),
                onFinish: () => setTesting(false),
                preserveState: true,
            },
        );
    }

    return (
        <AdminLayout>
            <Head title="CRM Integration" />

            <div className="max-w-xl space-y-6">
                <div>
                    <h1 className="text-xl font-semibold text-zinc-900 dark:text-zinc-100">CRM Integration</h1>
                    <p className="mt-1 text-sm text-zinc-500">
                        Connect to inteteam CRM to enable gallery, storefront, and form blocks.
                    </p>
                </div>

                {crm.is_connected && (
                    <div className="flex items-center gap-2 rounded-lg border border-green-200 bg-green-50 dark:border-green-800 dark:bg-green-900/20 px-4 py-3 text-sm text-green-700 dark:text-green-400">
                        <span className="h-2 w-2 rounded-full bg-green-500 shrink-0" />
                        CRM is connected
                    </div>
                )}

                <form onSubmit={submit} className="rounded-xl border border-zinc-200 dark:border-zinc-700 overflow-hidden">
                    <div className="px-4 py-3 bg-zinc-50 dark:bg-zinc-800 border-b border-zinc-200 dark:border-zinc-700">
                        <h2 className="text-sm font-semibold text-zinc-700 dark:text-zinc-200">Credentials</h2>
                    </div>
                    <div className="p-4 space-y-4">
                        <Field label="CRM Base URL" hint="e.g. https://api.inte.team" error={errors.crm_base_url}>
                            <input
                                type="url"
                                className={inputCls}
                                value={data.crm_base_url}
                                onChange={e => setData('crm_base_url', e.target.value)}
                                placeholder="https://api.inte.team"
                            />
                        </Field>

                        <Field label="Company ID" hint="Your CRM company identifier" error={errors.crm_company_id}>
                            <input
                                type="text"
                                className={inputCls}
                                value={data.crm_company_id}
                                onChange={e => setData('crm_company_id', e.target.value)}
                                placeholder="your-company-id"
                            />
                        </Field>

                        <Field
                            label="API Key"
                            hint={crm.has_api_key ? 'Leave blank to keep existing key.' : 'Enter your CRM API key.'}
                            error={errors.crm_api_key}
                        >
                            <input
                                type="password"
                                className={inputCls}
                                value={data.crm_api_key}
                                onChange={e => setData('crm_api_key', e.target.value)}
                                placeholder={crm.has_api_key ? '●●●●●●●●' : 'sk-...'}
                                autoComplete="new-password"
                            />
                        </Field>
                    </div>
                    <div className="px-4 py-3 bg-zinc-50 dark:bg-zinc-800 border-t border-zinc-200 dark:border-zinc-700 flex items-center gap-3">
                        <button
                            type="submit"
                            disabled={processing}
                            className="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50"
                        >
                            {processing ? 'Saving…' : 'Save'}
                        </button>

                        {crm.is_connected && (
                            <button
                                type="button"
                                onClick={testConnection}
                                disabled={testing}
                                className="rounded-lg border border-zinc-200 dark:border-zinc-700 px-4 py-2 text-sm font-medium text-zinc-700 dark:text-zinc-200 hover:bg-zinc-100 dark:hover:bg-zinc-700 disabled:opacity-50"
                            >
                                {testing ? 'Testing…' : 'Test Connection'}
                            </button>
                        )}

                        {testStatus && (
                            <span className={`text-sm ${testStatus.ok ? 'text-green-600' : 'text-red-500'}`}>
                                {testStatus.message}
                            </span>
                        )}
                    </div>
                </form>

                <div className="rounded-xl border border-zinc-200 dark:border-zinc-700 overflow-hidden">
                    <div className="px-4 py-3 bg-zinc-50 dark:bg-zinc-800 border-b border-zinc-200 dark:border-zinc-700">
                        <h2 className="text-sm font-semibold text-zinc-700 dark:text-zinc-200">Enabled CRM Block Types</h2>
                    </div>
                    <ul className="divide-y divide-zinc-100 dark:divide-zinc-800">
                        {[
                            { icon: '🖼', label: 'Gallery', description: 'Photo galleries from your CRM media library.' },
                            { icon: '🛒', label: 'Storefront', description: 'Product listings synced from your CRM catalogue.' },
                            { icon: '📋', label: 'Embedded Form', description: 'Lead capture and contact forms managed in CRM.' },
                            { icon: '📢', label: 'Business Updates', description: 'News and announcements from your CRM feed.' },
                        ].map(b => (
                            <li key={b.label} className="flex items-start gap-3 px-4 py-3">
                                <span className="text-lg mt-0.5">{b.icon}</span>
                                <div>
                                    <p className="text-sm font-medium text-zinc-800 dark:text-zinc-100">{b.label}</p>
                                    <p className="text-xs text-zinc-500">{b.description}</p>
                                </div>
                                {crm.is_connected && (
                                    <span className="ml-auto text-xs text-green-600 font-medium">Active</span>
                                )}
                            </li>
                        ))}
                    </ul>
                </div>
            </div>
        </AdminLayout>
    );
}
