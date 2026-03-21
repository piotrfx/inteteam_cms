import { Link, router, usePage } from '@inertiajs/react';
import { type ReactNode, useEffect, useState } from 'react';
import type { AppPageProps } from '@/types/inertia';

interface NavItem {
    label: string;
    href: string;
    icon: string;
}

const NAV_ITEMS: NavItem[] = [
    { label: 'Dashboard', href: 'admin.dashboard', icon: '⊞' },
    { label: 'Pages', href: 'admin.pages.index', icon: '📄' },
    { label: 'Posts', href: 'admin.posts.index', icon: '✍' },
    { label: 'Media', href: 'admin.media.index', icon: '🖼' },
    { label: 'Navigation', href: 'admin.navigation.index', icon: '☰' },
    { label: 'Settings', href: 'admin.settings.index', icon: '⚙' },
];

interface Props {
    children: ReactNode;
    title?: string;
}

export default function AdminLayout({ children, title }: Props) {
    const { auth, company, flash } = usePage<AppPageProps>().props;
    const [sidebarOpen, setSidebarOpen] = useState(false);
    const [flashMsg, setFlashMsg] = useState(flash);

    useEffect(() => {
        setFlashMsg(flash);
        if (flash.alert) {
            const t = setTimeout(() => setFlashMsg({ alert: null, type: null }), 4000);
            return () => clearTimeout(t);
        }
        return undefined;
    }, [flash]);

    function logout() {
        router.post(route('admin.logout'));
    }

    function isActive(routeName: string): boolean {
        try {
            return route().current(routeName) ?? false;
        } catch {
            return false;
        }
    }

    return (
        <div className="flex h-full min-h-screen bg-zinc-100 dark:bg-zinc-950">
            {/* ── Sidebar ────────────────────────────────────────────── */}
            <aside
                className={[
                    'fixed inset-y-0 left-0 z-40 flex w-60 flex-col bg-white dark:bg-zinc-900 border-r border-zinc-200 dark:border-zinc-800 transition-transform',
                    sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0',
                ].join(' ')}
            >
                {/* Logo */}
                <div className="flex h-14 items-center gap-2 border-b border-zinc-200 dark:border-zinc-800 px-4">
                    <span className="text-brand-600 font-bold text-lg">CMS</span>
                    {company && (
                        <span className="text-xs text-zinc-500 truncate">{company.name}</span>
                    )}
                </div>

                {/* Nav */}
                <nav className="flex-1 overflow-y-auto px-3 py-4 space-y-0.5">
                    {NAV_ITEMS.map((item) => {
                        let href: string;
                        let active = false;
                        try {
                            href = route(item.href);
                            active = isActive(item.href);
                        } catch {
                            href = '#';
                        }
                        return (
                            <Link
                                key={item.href}
                                href={href}
                                className={[
                                    'flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium transition-colors',
                                    active
                                        ? 'bg-brand-50 dark:bg-brand-950 text-brand-700 dark:text-brand-300'
                                        : 'text-zinc-600 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-800 hover:text-zinc-900 dark:hover:text-zinc-100',
                                ].join(' ')}
                            >
                                <span className="w-4 text-center text-base leading-none">{item.icon}</span>
                                {item.label}
                            </Link>
                        );
                    })}
                </nav>

                {/* User footer */}
                <div className="border-t border-zinc-200 dark:border-zinc-800 p-3">
                    <div className="flex items-center gap-2 rounded-md px-2 py-1.5">
                        <div className="flex-1 min-w-0">
                            <p className="text-sm font-medium truncate">{auth.user?.name}</p>
                            <p className="text-xs text-zinc-500 truncate">{auth.user?.role}</p>
                        </div>
                        <button
                            onClick={logout}
                            className="text-xs text-zinc-500 hover:text-zinc-900 dark:hover:text-zinc-100 shrink-0"
                            title="Sign out"
                        >
                            ⏻
                        </button>
                    </div>
                </div>
            </aside>

            {/* ── Overlay (mobile) ───────────────────────────────────── */}
            {sidebarOpen && (
                <div
                    className="fixed inset-0 z-30 bg-black/40 lg:hidden"
                    onClick={() => setSidebarOpen(false)}
                />
            )}

            {/* ── Main ──────────────────────────────────────────────── */}
            <div className="flex flex-1 flex-col lg:pl-60">
                {/* Header */}
                <header className="sticky top-0 z-20 flex h-14 items-center gap-4 border-b border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 px-4">
                    <button
                        className="lg:hidden text-zinc-500 hover:text-zinc-900 dark:hover:text-zinc-100"
                        onClick={() => setSidebarOpen(true)}
                        aria-label="Open menu"
                    >
                        ☰
                    </button>
                    {title && (
                        <h1 className="text-sm font-semibold text-zinc-900 dark:text-zinc-100">{title}</h1>
                    )}
                </header>

                {/* Flash message */}
                {flashMsg.alert && (
                    <div
                        className={[
                            'mx-4 mt-4 rounded-md px-4 py-3 text-sm',
                            flashMsg.type === 'success'
                                ? 'bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 text-green-700 dark:text-green-300'
                                : flashMsg.type === 'error'
                                  ? 'bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-300'
                                  : 'bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 text-blue-700 dark:text-blue-300',
                        ].join(' ')}
                    >
                        {flashMsg.alert}
                    </div>
                )}

                {/* Page content */}
                <main className="flex-1 p-6">{children}</main>
            </div>
        </div>
    );
}
