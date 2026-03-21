import { useState } from 'react';
import type { Block } from '@/types/models';
import BlockItem from './BlockItem';

interface Props {
    blocks: Block[];
    onChange: (blocks: Block[]) => void;
}

const BLOCK_TYPES = [
    { type: 'heading', label: 'Heading' },
    { type: 'rich_text', label: 'Rich Text' },
    { type: 'image', label: 'Image' },
    { type: 'cta', label: 'Call to Action' },
    { type: 'divider', label: 'Divider' },
] as const;

function defaultData(type: string): Record<string, unknown> {
    switch (type) {
        case 'heading':   return { text: '', level: 2 };
        case 'rich_text': return { html: '' };
        case 'image':     return { src: '', alt: '', caption: '' };
        case 'cta':       return { text: '', url: '', style: 'primary' };
        case 'divider':   return {};
        default:          return {};
    }
}

export default function BlockEditor({ blocks, onChange }: Props) {
    const [showPicker, setShowPicker] = useState(false);

    function addBlock(type: string) {
        const next: Block = { id: crypto.randomUUID(), type, data: defaultData(type) };
        onChange([...blocks, next]);
        setShowPicker(false);
    }

    function updateBlock(id: string, data: Record<string, unknown>) {
        onChange(blocks.map((b) => (b.id === id ? { ...b, data } : b)));
    }

    function removeBlock(id: string) {
        onChange(blocks.filter((b) => b.id !== id));
    }

    function moveBlock(id: string, dir: 'up' | 'down') {
        const idx = blocks.findIndex((b) => b.id === id);
        if (idx === -1) return;
        const next = [...blocks];
        const swap = dir === 'up' ? idx - 1 : idx + 1;
        if (swap < 0 || swap >= next.length) return;
        [next[idx], next[swap]] = [next[swap], next[idx]];
        onChange(next);
    }

    return (
        <div className="space-y-3">
            {blocks.map((block, idx) => (
                <BlockItem
                    key={block.id}
                    block={block}
                    isFirst={idx === 0}
                    isLast={idx === blocks.length - 1}
                    onUpdate={(data) => updateBlock(block.id, data)}
                    onRemove={() => removeBlock(block.id)}
                    onMove={(dir) => moveBlock(block.id, dir)}
                />
            ))}

            <div className="relative">
                <button
                    type="button"
                    onClick={() => setShowPicker(!showPicker)}
                    className="flex w-full items-center justify-center gap-2 rounded-lg border-2 border-dashed border-zinc-300 dark:border-zinc-600 py-3 text-sm text-zinc-500 dark:text-zinc-400 hover:border-indigo-400 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors"
                >
                    <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 4v16m8-8H4" />
                    </svg>
                    Add Block
                </button>

                {showPicker && (
                    <div className="absolute bottom-full left-0 mb-1 z-10 w-48 rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 shadow-lg py-1">
                        {BLOCK_TYPES.map(({ type, label }) => (
                            <button
                                key={type}
                                type="button"
                                onClick={() => addBlock(type)}
                                className="flex w-full items-center px-3 py-2 text-sm text-zinc-700 dark:text-zinc-200 hover:bg-zinc-50 dark:hover:bg-zinc-700 transition-colors"
                            >
                                {label}
                            </button>
                        ))}
                    </div>
                )}
            </div>
        </div>
    );
}
