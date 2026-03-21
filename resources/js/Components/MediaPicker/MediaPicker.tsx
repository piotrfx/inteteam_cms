import { useEffect, useState } from 'react';
import type { Media } from '@/types/models';
import MediaPickerGrid from './MediaPickerGrid';
import MediaPickerUpload from './MediaPickerUpload';

interface Props {
    value: string | null;
    onChange: (mediaId: string | null, media?: Media) => void;
    className?: string;
}

export default function MediaPicker({ value, onChange, className }: Props) {
    const [open, setOpen] = useState(false);
    const [preview, setPreview] = useState<Media | null>(null);

    useEffect(() => {
        if (!value) { setPreview(null); return; }
        // If we already have the media object cached, fine; otherwise show id only
    }, [value]);

    function handleSelect(media: Media) {
        setPreview(media);
        onChange(media.id, media);
        setOpen(false);
    }

    function handleClear() {
        setPreview(null);
        onChange(null);
    }

    return (
        <div className={className}>
            {/* Trigger */}
            <div className="flex items-center gap-2">
                {preview ? (
                    <div className="relative h-16 w-16 shrink-0 overflow-hidden rounded border border-zinc-300 dark:border-zinc-700">
                        <img src={preview.thumb_url} alt={preview.alt_text ?? preview.filename} className="h-full w-full object-cover" />
                    </div>
                ) : (
                    <div className="h-16 w-16 shrink-0 rounded border border-dashed border-zinc-300 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-900 flex items-center justify-center text-zinc-400 text-xl">
                        🖼
                    </div>
                )}

                <div className="flex flex-col gap-1">
                    <button
                        type="button"
                        onClick={() => setOpen(true)}
                        className="rounded px-2.5 py-1 text-sm border border-zinc-300 dark:border-zinc-700 hover:bg-zinc-100 dark:hover:bg-zinc-800"
                    >
                        {value ? 'Change image' : 'Select image'}
                    </button>
                    {value && (
                        <button type="button" onClick={handleClear} className="text-xs text-zinc-400 hover:text-red-500 text-left">
                            Remove
                        </button>
                    )}
                </div>
            </div>

            {/* Modal */}
            {open && (
                <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
                    <div className="absolute inset-0 bg-black/60" onClick={() => setOpen(false)} />
                    <div className="relative z-10 w-full max-w-3xl rounded-xl bg-white dark:bg-zinc-900 shadow-2xl flex flex-col max-h-[80vh]">
                        <div className="flex items-center justify-between border-b border-zinc-200 dark:border-zinc-800 px-4 py-3">
                            <h2 className="font-semibold">Select image</h2>
                            <button onClick={() => setOpen(false)} className="text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200 text-xl leading-none">×</button>
                        </div>
                        <div className="flex-1 overflow-y-auto p-4 space-y-4">
                            <MediaPickerUpload onUploaded={handleSelect} />
                            <MediaPickerGrid onSelect={handleSelect} selectedId={value} />
                        </div>
                    </div>
                </div>
            )}
        </div>
    );
}
