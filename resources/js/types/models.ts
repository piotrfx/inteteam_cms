export interface Media {
    id: string;
    filename: string;
    url: string;
    thumb_url: string;
    mime_type: string;
    size_bytes: number;
    width: number | null;
    height: number | null;
    alt_text: string | null;
    caption: string | null;
    created_at: string;
}

export interface CmsPage {
    id: string;
    title: string;
    slug: string;
    type: 'home' | 'about' | 'contact' | 'privacy' | 'terms' | 'custom';
    blocks: Block[];
    status: 'draft' | 'published';
    published_at: string | null;
    seo_title: string | null;
    seo_description: string | null;
    seo_og_image_path: string | null;
    seo_canonical_url: string | null;
    seo_robots: string | null;
    seo_schema_type: string;
    created_at: string;
    updated_at: string;
}

export interface CmsPost {
    id: string;
    title: string;
    slug: string;
    excerpt: string | null;
    blocks: Block[];
    status: 'draft' | 'published' | 'scheduled';
    published_at: string | null;
    featured_image_path: string | null;
    seo_title: string | null;
    seo_description: string | null;
    seo_og_image_path: string | null;
    seo_robots: string | null;
    author: { id: string; name: string } | null;
    created_at: string;
    updated_at: string;
}

export interface Block {
    id: string;
    type: string;
    data: Record<string, unknown>;
}

export interface PaginatedResult<T> {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
    links: Array<{ url: string | null; label: string; active: boolean }>;
}
