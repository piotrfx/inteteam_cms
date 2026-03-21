import { Head, router, useForm, usePage } from '@inertiajs/react';
import { useState } from 'react';
import AdminLayout from '@/Layouts/AdminLayout';
import type { Media, PaginatedResult } from '@/types/models';

interface Props {
    media: PaginatedResult<Media>;
    filters: { search: string };
}

function formatBytes(bytes: number): string {
    if (bytes < 1024) return `${bytes} B`;
    if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(0)} KB`;
    return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
}

export default function MediaIndex({ media, filters }: Props) {
    const [selected, setSelected] = useState<Media | null>(null);
    const [search, setSearch] = useState(filters.search ?? '');
    const [uploading, setUploading] = useState(false);
    const { data: editData, setData: setEditData, patch, processing } = useForm({
        alt_text: '',
        caption: '',
    });

    function handleSearch(e: React.FormEvent) {
        e.preventDefault();
        router.get(route('admin.media.index'), { search }, { preserveState: true, replace: true });
    }

    function handleUpload(e: React.ChangeEvent<HTMLInputElement>) {
        const file = e.target.files?.[0];
        if (!file) return;
        setUploading(true);
        const form = new FormData();
        form.append('file', file);
        fetch(route('admin.media.store'), {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: form,
        })
            .then((r) => r.json())
            .then(() => {
                router.reload({ only: ['media'] });
            })
            .finally(() => setUploading(false));
        e.target.value = '';
    }

    function selectMedia(item: Media) {
        setSelected(item);
        setEditData('alt_text', item.alt_text ?? '');
        setEditData('caption', item.caption ?? '');
    }

    function saveAlt() {
        if (!selected) return;
        patch(route('admin.media.update', selected.id), {
            onSuccess: () => router.reload({ only: ['media'] }),
        });
    }

    function deleteMedia() {
        if (!selected) return;
        if (!confirm(`Delete "${selected.filename}"?`)) return;
        router.delete(route('admin.media.destroy', selected.id), {
            onSuccess: () => setSelected(null),
        });
    }

    return (
        <AdminLayout title="Media Library">
            <Head title="Media Library" />

            {/* Toolbar */}
            <div className="mb-4 flex flex-wrap items-center gap-3">
                <form onSubmit={handleSearch} className="flex gap-2">
                    <input
                        type="search"
                        placeholder="Search by filename…"
                        value={search}
                        onChange={(e) => setSearch(e.target.value)}
                        className="rounded-md border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 px-3 py-1.5 text-sm"
                    />
                    <button type="submit" className="rounded-md bg-zinc-100 dark:bg-zinc-800 px-3 py-1.5 text-sm hover:bg-zinc-200 dark:hover:bg-zinc-700">
                        Search
                    </button>
                </form>

                <label className="cursor-pointer rounded-md bg-brand-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-brand-700">
                    {uploading ? 'Uploading…' : '+ Upload'}
                    <input type="file" accept="image/jpeg,image/png,image/webp,image/gif,image/svg+xml" onChange={handleUpload} className="sr-only" />
                </label>

                <span className="ml-auto text-xs text-zinc-500">{media.total} file{media.total !== 1 ? 's' : ''}</span>
            </div>

            <div className="flex gap-4">
                {/* Grid */}
                <div className="flex-1 min-w-0">
                    {media.data.length === 0 ? (
                        <div className="py-16 text-center text-zinc-500 text-sm">No media yet. Upload your first image.</div>
                    ) : (
                        <div className="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-5 lg:grid-cols-6 gap-2">
                            {media.data.map((item) => (
                                <button
                                    key={item.id}
                                    onClick={() => selectMedia(item)}
                                    className={[
                                        'group relative aspect-square overflow-hidden rounded-md border-2 bg-zinc-100 dark:bg-zinc-800',
                                        selected?.id === item.id ? 'border-brand-500' : 'border-transparent hover:border-zinc-300 dark:hover:border-zinc-600',
                                    ].join(' ')}
                                >
                                    <img
                                        src={item.thumb_url}
                                        alt={item.alt_text ?? item.filename}
                                        className="h-full w-full object-cover"
                                        loading="lazy"
                                    />
                                </button>
                            ))}
                        </div>
                    )}

                    {/* Pagination */}
                    {media.last_page > 1 && (
                        <div className="mt-4 flex gap-1">
                            {media.links.map((link, i) => (
                                <button
                                    key={i}
                                    disabled={!link.url}
                                    onClick={() => link.url && router.get(link.url)}
                                    className={[
                                        'px-3 py-1.5 text-sm rounded',
                                        link.active ? 'bg-brand-600 text-white' : 'bg-zinc-100 dark:bg-zinc-800 hover:bg-zinc-200 dark:hover:bg-zinc-700',
                                        !link.url ? 'opacity-40 cursor-not-allowed' : '',
                                    ].join(' ')}
                                    dangerouslySetInnerHTML={{ __html: link.label }}
                                />
                            ))}
                        </div>
                    )}
                </div>

                {/* Detail sidebar */}
                {selected && (
                    <aside className="w-64 shrink-0 space-y-4 rounded-lg border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 p-4">
                        <img src={selected.url} alt={selected.alt_text ?? selected.filename} className="w-full rounded object-contain max-h-40" />
                        <div className="space-y-1 text-xs text-zinc-500">
                            <p className="font-medium text-zinc-800 dark:text-zinc-200 truncate">{selected.filename}</p>
                            <p>{formatBytes(selected.size_bytes)}</p>
                            {selected.width && <p>{selected.width} × {selected.height}px</p>}
                        </div>

                        <div className="space-y-2">
                            <div>
                                <label className="block text-xs font-medium mb-1">Alt text</label>
                                <input
                                    value={editData.alt_text}
                                    onChange={(e) => setEditData('alt_text', e.target.value)}
                                    className="w-full rounded border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 px-2 py-1.5 text-sm"
                                />
                            </div>
                            <div>
                                <label className="block text-xs font-medium mb-1">Caption</label>
                                <input
                                    value={editData.caption}
                                    onChange={(e) => setEditData('caption', e.target.value)}
                                    className="w-full rounded border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 px-2 py-1.5 text-sm"
                                />
                            </div>
                            <button
                                onClick={saveAlt}
                                disabled={processing}
                                className="w-full rounded bg-brand-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-brand-700 disabled:opacity-50"
                            >
                                Save
                            </button>
                        </div>

                        <button
                            onClick={deleteMedia}
                            className="w-full rounded border border-red-200 dark:border-red-800 px-3 py-1.5 text-sm text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20"
                        >
                            Delete
                        </button>
                    </aside>
                )}
            </div>
        </AdminLayout>
    );
}
