import { useRef, useState } from 'react';
import type { Media } from '@/types/models';

interface Props {
    onUploaded: (media: Media) => void;
}

export default function MediaPickerUpload({ onUploaded }: Props) {
    const [dragging, setDragging] = useState(false);
    const [uploading, setUploading] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const inputRef = useRef<HTMLInputElement>(null);

    async function uploadFile(file: File) {
        setUploading(true);
        setError(null);
        const form = new FormData();
        form.append('file', file);

        try {
            const res = await fetch(route('admin.media.store'), {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: form,
            });
            const json = await res.json();
            if (!res.ok) throw new Error(json.message ?? 'Upload failed');
            onUploaded(json as Media);
        } catch (e: unknown) {
            setError(e instanceof Error ? e.message : 'Upload failed');
        } finally {
            setUploading(false);
        }
    }

    function handleDrop(e: React.DragEvent) {
        e.preventDefault();
        setDragging(false);
        const file = e.dataTransfer.files[0];
        if (file) uploadFile(file);
    }

    function handleChange(e: React.ChangeEvent<HTMLInputElement>) {
        const file = e.target.files?.[0];
        if (file) uploadFile(file);
        e.target.value = '';
    }

    return (
        <div
            onDragOver={(e) => { e.preventDefault(); setDragging(true); }}
            onDragLeave={() => setDragging(false)}
            onDrop={handleDrop}
            onClick={() => inputRef.current?.click()}
            className={[
                'flex cursor-pointer flex-col items-center justify-center gap-1 rounded-lg border-2 border-dashed px-4 py-6 text-sm text-zinc-500 transition-colors',
                dragging ? 'border-brand-400 bg-brand-50 dark:bg-brand-950' : 'border-zinc-300 dark:border-zinc-700 hover:border-zinc-400',
            ].join(' ')}
        >
            <input ref={inputRef} type="file" accept="image/jpeg,image/png,image/webp,image/gif,image/svg+xml" className="sr-only" onChange={handleChange} />
            {uploading ? <span>Uploading…</span> : <span>Drop an image here, or click to upload</span>}
            {error && <span className="text-red-500">{error}</span>}
        </div>
    );
}
