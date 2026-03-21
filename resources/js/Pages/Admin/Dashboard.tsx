import { Head, usePage } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import type { AppPageProps } from '@/types/inertia';

export default function Dashboard() {
    const { auth, company } = usePage<AppPageProps>().props;

    return (
        <AdminLayout title="Dashboard">
            <Head title="Dashboard" />

            <div className="space-y-6">
                <div>
                    <h2 className="text-2xl font-bold tracking-tight">
                        Welcome back, {auth.user?.name}
                    </h2>
                    {company && (
                        <p className="mt-1 text-sm text-zinc-500">
                            Managing <span className="font-medium">{company.name}</span>
                        </p>
                    )}
                </div>

                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    {[
                        { label: 'Pages', desc: 'Manage your site pages', href: 'admin.pages.index' },
                        { label: 'Posts', desc: 'Manage blog posts', href: 'admin.posts.index' },
                        { label: 'Media', desc: 'Manage uploaded files', href: 'admin.media.index' },
                    ].map((card) => {
                        let href = '#';
                        try { href = route(card.href); } catch {}
                        return (
                            <a
                                key={card.label}
                                href={href}
                                className="rounded-lg border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 p-5 hover:border-brand-300 dark:hover:border-brand-700 transition-colors"
                            >
                                <p className="font-semibold">{card.label}</p>
                                <p className="mt-1 text-sm text-zinc-500">{card.desc}</p>
                            </a>
                        );
                    })}
                </div>
            </div>
        </AdminLayout>
    );
}
