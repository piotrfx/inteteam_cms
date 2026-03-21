import './bootstrap';

import { createInertiaApp } from '@inertiajs/react';
import { createRoot } from 'react-dom/client';

createInertiaApp({
    title: (title) => (title ? `${title} — Inte.Team CMS` : 'Inte.Team CMS'),
    resolve: (name) => {
        const pages = import.meta.glob('./Pages/**/*.tsx', { eager: true });
        const page = pages[`./Pages/${name}.tsx`];
        if (!page) throw new Error(`Page not found: ${name}`);
        return page as Parameters<typeof createInertiaApp>[0]['resolve'] extends (name: string) => infer R ? R : never;
    },
    setup({ el, App, props }) {
        const root = createRoot(el);
        root.render(<App {...props} />);
    },
    progress: {
        color: '#6366f1',
    },
});
