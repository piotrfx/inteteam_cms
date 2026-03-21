import { Head, useForm } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';

interface CompanySettings {
    name: string;
    primary_colour: string;
    theme: string;
    logo_path: string | null;
    favicon_path: string | null;
    seo_site_name: string | null;
    seo_title_suffix: string | null;
    seo_meta_description: string | null;
    seo_robots: string;
    seo_google_verification: string | null;
    seo_twitter_handle: string | null;
    seo_address_street: string | null;
    seo_address_city: string | null;
    seo_address_postcode: string | null;
    seo_phone: string | null;
    seo_price_range: string | null;
}

interface Props {
    company: CompanySettings;
}

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

function Section({ title, children }: { title: string; children: React.ReactNode }) {
    return (
        <div className="rounded-xl border border-zinc-200 dark:border-zinc-700 overflow-hidden">
            <div className="px-4 py-3 bg-zinc-50 dark:bg-zinc-800 border-b border-zinc-200 dark:border-zinc-700">
                <h2 className="text-sm font-semibold text-zinc-700 dark:text-zinc-200">{title}</h2>
            </div>
            <div className="p-4 space-y-4">{children}</div>
        </div>
    );
}

const inputCls = 'w-full rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 text-sm text-zinc-900 dark:text-zinc-100 px-3 py-2 placeholder-zinc-400 focus:outline-none focus:ring-1 focus:ring-indigo-500';

export default function SettingsIndex({ company }: Props) {
    const { data, setData, post, processing, errors } = useForm({
        name: company.name,
        primary_colour: company.primary_colour,
        theme: company.theme,
        seo_site_name: company.seo_site_name ?? '',
        seo_title_suffix: company.seo_title_suffix ?? '',
        seo_meta_description: company.seo_meta_description ?? '',
        seo_robots: company.seo_robots,
        seo_google_verification: company.seo_google_verification ?? '',
        seo_twitter_handle: company.seo_twitter_handle ?? '',
        seo_address_street: company.seo_address_street ?? '',
        seo_address_city: company.seo_address_city ?? '',
        seo_address_postcode: company.seo_address_postcode ?? '',
        seo_phone: company.seo_phone ?? '',
        seo_price_range: company.seo_price_range ?? '',
    });

    function submit(e: React.FormEvent) {
        e.preventDefault();
        post(route('admin.settings.update'));
    }

    return (
        <AdminLayout title="Settings">
            <Head title="Settings" />

            <div className="mb-6">
                <h1 className="text-xl font-semibold text-zinc-900 dark:text-zinc-100">Site Settings</h1>
                <p className="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                    Manage your site's branding, SEO defaults, and business details.
                </p>
            </div>

            <form onSubmit={submit}>
                <div className="space-y-6 max-w-2xl">
                    {/* Branding */}
                    <Section title="Branding">
                        <Field label="Site Name" error={errors.name}>
                            <input type="text" value={data.name}
                                onChange={(e) => setData('name', e.target.value)}
                                className={inputCls} />
                        </Field>

                        <Field label="Brand Colour" hint="Used as the primary accent colour on your public site.">
                            <div className="flex items-center gap-3">
                                <input type="color" value={data.primary_colour}
                                    onChange={(e) => setData('primary_colour', e.target.value)}
                                    className="h-10 w-16 rounded-lg border border-zinc-200 dark:border-zinc-700 cursor-pointer p-0.5" />
                                <input type="text" value={data.primary_colour}
                                    onChange={(e) => setData('primary_colour', e.target.value)}
                                    className="w-32 rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 text-sm px-3 py-2 font-mono text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-1 focus:ring-indigo-500" />
                            </div>
                            {errors.primary_colour && <p className="mt-1 text-xs text-red-500">{errors.primary_colour}</p>}
                        </Field>

                        <Field label="Theme">
                            <select value={data.theme} onChange={(e) => setData('theme', e.target.value)}
                                className={inputCls}>
                                <option value="default">Default</option>
                            </select>
                        </Field>
                    </Section>

                    {/* SEO Defaults */}
                    <Section title="SEO Defaults">
                        <Field label="Site Name (OG)" hint="Shown in Open Graph previews.">
                            <input type="text" value={data.seo_site_name}
                                onChange={(e) => setData('seo_site_name', e.target.value)}
                                className={inputCls} placeholder="e.g. Acme Repairs" />
                        </Field>
                        <Field label="Title Suffix" hint="Appended to every page title. Leave blank to use '| Site Name'.">
                            <input type="text" value={data.seo_title_suffix}
                                onChange={(e) => setData('seo_title_suffix', e.target.value)}
                                className={inputCls} placeholder="e.g.  | Acme Repairs" />
                        </Field>
                        <Field label="Default Meta Description" error={errors.seo_meta_description}>
                            <textarea value={data.seo_meta_description}
                                onChange={(e) => setData('seo_meta_description', e.target.value)}
                                rows={3} maxLength={160}
                                className={inputCls + ' resize-none'} />
                        </Field>
                        <div className="grid grid-cols-2 gap-4">
                            <Field label="Default Robots">
                                <select value={data.seo_robots} onChange={(e) => setData('seo_robots', e.target.value)}
                                    className={inputCls}>
                                    <option value="index">index, follow</option>
                                    <option value="noindex">noindex</option>
                                </select>
                            </Field>
                            <Field label="Twitter Handle">
                                <input type="text" value={data.seo_twitter_handle}
                                    onChange={(e) => setData('seo_twitter_handle', e.target.value)}
                                    className={inputCls} placeholder="@yourhandle" />
                            </Field>
                        </div>
                        <Field label="Google Verification Code">
                            <input type="text" value={data.seo_google_verification}
                                onChange={(e) => setData('seo_google_verification', e.target.value)}
                                className={inputCls} placeholder="Paste code from Google Search Console" />
                        </Field>
                    </Section>

                    {/* Business Details (LocalBusiness JSON-LD) */}
                    <Section title="Business Details">
                        <p className="text-xs text-zinc-400">Used in structured data (JSON-LD) on your home page to help Google show your business in local search.</p>
                        <Field label="Street Address">
                            <input type="text" value={data.seo_address_street}
                                onChange={(e) => setData('seo_address_street', e.target.value)}
                                className={inputCls} />
                        </Field>
                        <div className="grid grid-cols-2 gap-4">
                            <Field label="City">
                                <input type="text" value={data.seo_address_city}
                                    onChange={(e) => setData('seo_address_city', e.target.value)}
                                    className={inputCls} />
                            </Field>
                            <Field label="Postcode">
                                <input type="text" value={data.seo_address_postcode}
                                    onChange={(e) => setData('seo_address_postcode', e.target.value)}
                                    className={inputCls} />
                            </Field>
                        </div>
                        <div className="grid grid-cols-2 gap-4">
                            <Field label="Phone">
                                <input type="text" value={data.seo_phone}
                                    onChange={(e) => setData('seo_phone', e.target.value)}
                                    className={inputCls} placeholder="+44 ..." />
                            </Field>
                            <Field label="Price Range">
                                <input type="text" value={data.seo_price_range}
                                    onChange={(e) => setData('seo_price_range', e.target.value)}
                                    className={inputCls} placeholder="£ / ££ / £££" />
                            </Field>
                        </div>
                    </Section>

                    <div className="flex justify-end">
                        <button type="submit" disabled={processing}
                            className="rounded-lg bg-indigo-600 px-6 py-2 text-sm font-medium text-white hover:bg-indigo-700 transition-colors disabled:opacity-60">
                            Save Settings
                        </button>
                    </div>
                </div>
            </form>
        </AdminLayout>
    );
}
