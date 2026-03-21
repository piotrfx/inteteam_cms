import { useEffect, useState } from 'react';
import type { Media, PaginatedResult } from '@/types/models';

interface Props {
    onSelect: (media: Media) => void;
    selectedId: string | null;
}

export default function MediaPickerGrid({ onSelect, selectedId }: Props) {
    const [page, setPage] = useState(1);
    const [result, setResult] = useState<PaginatedResult<Media> | null>(null);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        setLoading(true);
        fetch(`${route('admin.media.index')}?page=${page}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-Inertia': 'true' },
        })
            .then((r) => r.json())
            .then((data) => {
                // Inertia response wraps props
                const props = data.props ?? data;
                setResult(props.media ?? null);
            })
            .finally(() => setLoading(false));
    }, [page]);

    if (loading) {
        return <div className="py-8 text-center text-sm text-zinc-500">Loading…</div>;
    }

    if (!result || result.data.length === 0) {
        return <div className="py-8 text-center text-sm text-zinc-500">No media uploaded yet.</div>;
    }

    return (
        <div className="space-y-3">
            <div className="grid grid-cols-4 sm:grid-cols-5 md:grid-cols-6 gap-2">
                {result.data.map((item) => (
                    <button
                        key={item.id}
                        onClick={() => onSelect(item)}
                        className={[
                            'aspect-square overflow-hidden rounded border-2 bg-zinc-100 dark:bg-zinc-800',
                            selectedId === item.id ? 'border-brand-500' : 'border-transparent hover:border-zinc-300 dark:hover:border-zinc-600',
                        ].join(' ')}
                    >
                        <img src={item.thumb_url} alt={item.alt_text ?? item.filename} className="h-full w-full object-cover" loading="lazy" />
                    </button>
                ))}
            </div>

            {result.last_page > 1 && (
                <div className="flex justify-center gap-2">
                    <button disabled={page <= 1} onClick={() => setPage(p => p - 1)} className="rounded px-3 py-1 text-sm bg-zinc-100 dark:bg-zinc-800 disabled:opacity-40">
                        ← Prev
                    </button>
                    <span className="text-sm text-zinc-500">{page} / {result.last_page}</span>
                    <button disabled={page >= result.last_page} onClick={() => setPage(p => p + 1)} className="rounded px-3 py-1 text-sm bg-zinc-100 dark:bg-zinc-800 disabled:opacity-40">
                        Next →
                    </button>
                </div>
            )}
        </div>
    );
}
