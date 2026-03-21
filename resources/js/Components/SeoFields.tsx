import { useState } from 'react';

interface SeoData {
    seo_title: string;
    seo_description: string;
    seo_og_image_path: string;
    seo_canonical_url: string;
    seo_robots: string;
    seo_schema_type: string;
}

interface Props {
    data: SeoData;
    errors: Partial<Record<keyof SeoData, string>>;
    onChange: (key: keyof SeoData, value: string) => void;
}

export default function SeoFields({ data, errors, onChange }: Props) {
    const [open, setOpen] = useState(false);

    return (
        <div className="rounded-xl border border-zinc-200 dark:border-zinc-700">
            <button
                type="button"
                onClick={() => setOpen(!open)}
                className="flex w-full items-center justify-between px-4 py-3 text-sm font-medium text-zinc-700 dark:text-zinc-200"
            >
                <span>SEO Settings</span>
                <svg
                    className={`w-4 h-4 text-zinc-400 transition-transform ${open ? 'rotate-180' : ''}`}
                    fill="none" stroke="currentColor" viewBox="0 0 24 24"
                >
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 9l-7 7-7-7" />
                </svg>
            </button>

            {open && (
                <div className="border-t border-zinc-200 dark:border-zinc-700 p-4 space-y-4">
                    <Field label="SEO Title" error={errors.seo_title}>
                        <input
                            type="text"
                            value={data.seo_title}
                            onChange={(e) => onChange('seo_title', e.target.value)}
                            placeholder="Leave blank to use page title"
                            className="w-full rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 text-sm text-zinc-900 dark:text-zinc-100 px-3 py-2 placeholder-zinc-400 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                        />
                    </Field>

                    <Field label="Meta Description" error={errors.seo_description}>
                        <textarea
                            value={data.seo_description}
                            onChange={(e) => onChange('seo_description', e.target.value)}
                            placeholder="Up to 160 characters"
                            rows={3}
                            maxLength={160}
                            className="w-full rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 text-sm text-zinc-900 dark:text-zinc-100 px-3 py-2 placeholder-zinc-400 focus:outline-none focus:ring-1 focus:ring-indigo-500 resize-none"
                        />
                    </Field>

                    <Field label="OG Image Path" error={errors.seo_og_image_path}>
                        <input
                            type="text"
                            value={data.seo_og_image_path}
                            onChange={(e) => onChange('seo_og_image_path', e.target.value)}
                            placeholder="/storage/media/…"
                            className="w-full rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 text-sm text-zinc-900 dark:text-zinc-100 px-3 py-2 placeholder-zinc-400 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                        />
                    </Field>

                    <Field label="Canonical URL" error={errors.seo_canonical_url}>
                        <input
                            type="url"
                            value={data.seo_canonical_url}
                            onChange={(e) => onChange('seo_canonical_url', e.target.value)}
                            placeholder="https://…"
                            className="w-full rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 text-sm text-zinc-900 dark:text-zinc-100 px-3 py-2 placeholder-zinc-400 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                        />
                    </Field>

                    <div className="grid grid-cols-2 gap-4">
                        <Field label="Robots" error={errors.seo_robots}>
                            <select
                                value={data.seo_robots}
                                onChange={(e) => onChange('seo_robots', e.target.value)}
                                className="w-full rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 text-sm text-zinc-700 dark:text-zinc-200 px-3 py-2"
                            >
                                <option value="">Default</option>
                                <option value="index">index</option>
                                <option value="follow">follow</option>
                                <option value="noindex">noindex</option>
                                <option value="nofollow">nofollow</option>
                            </select>
                        </Field>

                        <Field label="Schema Type" error={errors.seo_schema_type}>
                            <select
                                value={data.seo_schema_type}
                                onChange={(e) => onChange('seo_schema_type', e.target.value)}
                                className="w-full rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 text-sm text-zinc-700 dark:text-zinc-200 px-3 py-2"
                            >
                                <option value="WebPage">WebPage</option>
                                <option value="FAQPage">FAQPage</option>
                                <option value="ContactPage">ContactPage</option>
                            </select>
                        </Field>
                    </div>
                </div>
            )}
        </div>
    );
}

function Field({ label, error, children }: { label: string; error?: string; children: React.ReactNode }) {
    return (
        <div>
            <label className="block text-xs font-medium text-zinc-600 dark:text-zinc-400 mb-1">{label}</label>
            {children}
            {error && <p className="mt-1 text-xs text-red-500">{error}</p>}
        </div>
    );
}
