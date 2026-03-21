import { Head, router } from '@inertiajs/react';
import { useState } from 'react';
import AdminLayout from '@/Layouts/AdminLayout';

interface NavItem {
    label: string;
    url: string;
    target: '_self' | '_blank';
}

interface Props {
    header: NavItem[];
    footer: NavItem[];
}

function NavEditor({
    location,
    items,
    onSave,
}: {
    location: 'header' | 'footer';
    items: NavItem[];
    onSave: (location: 'header' | 'footer', items: NavItem[]) => void;
}) {
    const [localItems, setLocalItems] = useState<NavItem[]>(items);

    function addItem() {
        setLocalItems([...localItems, { label: '', url: '', target: '_self' }]);
    }

    function removeItem(idx: number) {
        setLocalItems(localItems.filter((_, i) => i !== idx));
    }

    function updateItem(idx: number, field: keyof NavItem, value: string) {
        setLocalItems(localItems.map((item, i) =>
            i === idx ? { ...item, [field]: value } : item,
        ));
    }

    function moveItem(idx: number, dir: 'up' | 'down') {
        const next = [...localItems];
        const swap = dir === 'up' ? idx - 1 : idx + 1;
        if (swap < 0 || swap >= next.length) return;
        [next[idx], next[swap]] = [next[swap], next[idx]];
        setLocalItems(next);
    }

    return (
        <div className="rounded-xl border border-zinc-200 dark:border-zinc-700 overflow-hidden">
            <div className="flex items-center justify-between px-4 py-3 bg-zinc-50 dark:bg-zinc-800 border-b border-zinc-200 dark:border-zinc-700">
                <h2 className="text-sm font-semibold text-zinc-700 dark:text-zinc-200 capitalize">{location} Navigation</h2>
                <button
                    type="button"
                    onClick={() => onSave(location, localItems)}
                    className="text-xs bg-indigo-600 text-white px-3 py-1.5 rounded-lg hover:bg-indigo-700 transition-colors font-medium"
                >
                    Save
                </button>
            </div>

            <div className="p-4 space-y-2">
                {localItems.length === 0 && (
                    <p className="text-sm text-zinc-400 text-center py-4">No items yet.</p>
                )}

                {localItems.map((item, idx) => (
                    <div key={idx} className="flex items-center gap-2">
                        <div className="flex flex-col gap-0.5">
                            <button type="button" onClick={() => moveItem(idx, 'up')} disabled={idx === 0}
                                className="text-zinc-400 hover:text-zinc-700 disabled:opacity-30 text-xs leading-none">▲</button>
                            <button type="button" onClick={() => moveItem(idx, 'down')} disabled={idx === localItems.length - 1}
                                className="text-zinc-400 hover:text-zinc-700 disabled:opacity-30 text-xs leading-none">▼</button>
                        </div>
                        <input
                            type="text"
                            value={item.label}
                            onChange={(e) => updateItem(idx, 'label', e.target.value)}
                            placeholder="Label"
                            className="w-36 rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 text-sm px-2 py-1.5 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                        />
                        <input
                            type="text"
                            value={item.url}
                            onChange={(e) => updateItem(idx, 'url', e.target.value)}
                            placeholder="URL or /slug"
                            className="flex-1 rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 text-sm px-2 py-1.5 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                        />
                        <select
                            value={item.target}
                            onChange={(e) => updateItem(idx, 'target', e.target.value)}
                            className="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 text-sm px-2 py-1.5 text-zinc-700 dark:text-zinc-200"
                        >
                            <option value="_self">Same tab</option>
                            <option value="_blank">New tab</option>
                        </select>
                        <button
                            type="button"
                            onClick={() => removeItem(idx)}
                            className="text-red-400 hover:text-red-600 text-sm font-medium"
                        >✕</button>
                    </div>
                ))}

                <button
                    type="button"
                    onClick={addItem}
                    className="mt-2 flex items-center gap-1.5 text-sm text-indigo-600 hover:text-indigo-700 dark:text-indigo-400"
                >
                    <span className="text-base leading-none font-bold">+</span> Add link
                </button>
            </div>
        </div>
    );
}

export default function NavigationIndex({ header, footer }: Props) {
    function handleSave(location: 'header' | 'footer', items: NavItem[]) {
        router.post(route('admin.navigation.update'), { location, items });
    }

    return (
        <AdminLayout title="Navigation">
            <Head title="Navigation" />

            <div className="mb-6">
                <h1 className="text-xl font-semibold text-zinc-900 dark:text-zinc-100">Navigation</h1>
                <p className="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                    Build the header and footer menus for your public site.
                </p>
            </div>

            <div className="space-y-6 max-w-2xl">
                <NavEditor location="header" items={header} onSave={handleSave} />
                <NavEditor location="footer" items={footer} onSave={handleSave} />
            </div>
        </AdminLayout>
    );
}
