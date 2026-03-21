import type { Page, PageProps } from '@inertiajs/core';

export interface AuthUser {
    id: string;
    name: string;
    email: string;
    role: 'admin' | 'editor' | 'viewer';
}

export interface SharedCompany {
    id: string;
    name: string;
    slug: string;
    theme: string;
    primary_colour: string | null;
    logo_path: string | null;
}

export interface SharedFlash {
    alert: string | null;
    type: 'success' | 'error' | 'info' | null;
}

export interface AppPageProps extends PageProps {
    auth: {
        user: AuthUser | null;
    };
    company: SharedCompany | null;
    flash: SharedFlash;
}
