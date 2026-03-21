import type { Block } from '@/types/models';

interface Props {
    block: Block;
    isFirst: boolean;
    isLast: boolean;
    onUpdate: (data: Record<string, unknown>) => void;
    onRemove: () => void;
    onMove: (dir: 'up' | 'down') => void;
}

export default function BlockItem({ block, isFirst, isLast, onUpdate, onRemove, onMove }: Props) {
    return (
        <div className="group relative rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900">
            {/* toolbar */}
            <div className="flex items-center justify-between border-b border-zinc-100 dark:border-zinc-800 px-3 py-1.5">
                <span className="text-xs font-medium text-zinc-500 dark:text-zinc-400 capitalize">{block.type.replace('_', ' ')}</span>
                <div className="flex items-center gap-1">
                    <button
                        type="button"
                        disabled={isFirst}
                        onClick={() => onMove('up')}
                        className="rounded p-0.5 text-zinc-400 hover:text-zinc-700 dark:hover:text-zinc-200 disabled:opacity-30 transition-colors"
                        title="Move up"
                    >
                        <svg className="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 15l7-7 7 7" />
                        </svg>
                    </button>
                    <button
                        type="button"
                        disabled={isLast}
                        onClick={() => onMove('down')}
                        className="rounded p-0.5 text-zinc-400 hover:text-zinc-700 dark:hover:text-zinc-200 disabled:opacity-30 transition-colors"
                        title="Move down"
                    >
                        <svg className="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
                    <button
                        type="button"
                        onClick={onRemove}
                        className="rounded p-0.5 text-zinc-400 hover:text-red-500 dark:hover:text-red-400 transition-colors"
                        title="Remove block"
                    >
                        <svg className="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>

            {/* fields */}
            <div className="p-3">
                <BlockFields block={block} onUpdate={onUpdate} />
            </div>
        </div>
    );
}

function BlockFields({ block, onUpdate }: { block: Block; onUpdate: (data: Record<string, unknown>) => void }) {
    const d = block.data as Record<string, string>;
    const set = (key: string, value: string) => onUpdate({ ...block.data, [key]: value });

    switch (block.type) {
        case 'heading':
            return (
                <div className="space-y-2">
                    <div className="flex gap-2">
                        <select
                            value={String(d.level ?? 2)}
                            onChange={(e) => set('level', e.target.value)}
                            className="rounded border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 text-xs text-zinc-700 dark:text-zinc-200 px-2 py-1"
                        >
                            {[1, 2, 3, 4].map((l) => (
                                <option key={l} value={l}>H{l}</option>
                            ))}
                        </select>
                        <input
                            type="text"
                            value={d.text ?? ''}
                            onChange={(e) => set('text', e.target.value)}
                            placeholder="Heading text"
                            className="flex-1 rounded border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 text-sm text-zinc-900 dark:text-zinc-100 px-3 py-1.5 placeholder-zinc-400 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                        />
                    </div>
                </div>
            );

        case 'rich_text':
            return (
                <textarea
                    value={d.html ?? ''}
                    onChange={(e) => set('html', e.target.value)}
                    placeholder="Enter HTML content…"
                    rows={5}
                    className="w-full rounded border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 text-sm text-zinc-900 dark:text-zinc-100 px-3 py-2 placeholder-zinc-400 font-mono focus:outline-none focus:ring-1 focus:ring-indigo-500 resize-y"
                />
            );

        case 'image':
            return (
                <div className="space-y-2">
                    <input
                        type="text"
                        value={d.src ?? ''}
                        onChange={(e) => set('src', e.target.value)}
                        placeholder="Image URL or path"
                        className="w-full rounded border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 text-sm text-zinc-900 dark:text-zinc-100 px-3 py-1.5 placeholder-zinc-400 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                    />
                    <input
                        type="text"
                        value={d.alt ?? ''}
                        onChange={(e) => set('alt', e.target.value)}
                        placeholder="Alt text"
                        className="w-full rounded border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 text-sm text-zinc-900 dark:text-zinc-100 px-3 py-1.5 placeholder-zinc-400 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                    />
                    <input
                        type="text"
                        value={d.caption ?? ''}
                        onChange={(e) => set('caption', e.target.value)}
                        placeholder="Caption (optional)"
                        className="w-full rounded border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 text-sm text-zinc-900 dark:text-zinc-100 px-3 py-1.5 placeholder-zinc-400 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                    />
                </div>
            );

        case 'cta':
            return (
                <div className="space-y-2">
                    <input
                        type="text"
                        value={d.text ?? ''}
                        onChange={(e) => set('text', e.target.value)}
                        placeholder="Button text"
                        className="w-full rounded border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 text-sm text-zinc-900 dark:text-zinc-100 px-3 py-1.5 placeholder-zinc-400 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                    />
                    <input
                        type="text"
                        value={d.url ?? ''}
                        onChange={(e) => set('url', e.target.value)}
                        placeholder="URL"
                        className="w-full rounded border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 text-sm text-zinc-900 dark:text-zinc-100 px-3 py-1.5 placeholder-zinc-400 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                    />
                    <select
                        value={d.style ?? 'primary'}
                        onChange={(e) => set('style', e.target.value)}
                        className="w-full rounded border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 text-sm text-zinc-700 dark:text-zinc-200 px-3 py-1.5"
                    >
                        <option value="primary">Primary</option>
                        <option value="secondary">Secondary</option>
                        <option value="outline">Outline</option>
                    </select>
                </div>
            );

        case 'divider':
            return <div className="border-t border-zinc-200 dark:border-zinc-700 my-1" />;

        default:
            return (
                <textarea
                    value={JSON.stringify(block.data, null, 2)}
                    onChange={(e) => {
                        try { onUpdate(JSON.parse(e.target.value)); } catch { /* ignore */ }
                    }}
                    rows={4}
                    className="w-full rounded border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 text-xs text-zinc-900 dark:text-zinc-100 px-3 py-2 font-mono focus:outline-none focus:ring-1 focus:ring-indigo-500 resize-y"
                />
            );
    }
}
