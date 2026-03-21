@extends('themes.default.layouts.public')

@section('content')
    <article style="max-width:720px;">
        @if($post->featured_image_path)
            <img src="{{ Storage::url($post->featured_image_path) }}"
                 alt="{{ $post->title }}"
                 style="width:100%;border-radius:0.75rem;margin-bottom:2rem;">
        @endif

        <header style="margin-bottom:2rem;">
            <h1 style="font-size:2rem;font-weight:800;line-height:1.2;margin:0 0 0.75rem;">
                {{ $post->title }}
            </h1>
            <div style="font-size:0.85rem;color:#6b7280;display:flex;gap:1rem;flex-wrap:wrap;">
                @if($post->author)
                    <span>By {{ $post->author->name }}</span>
                @endif
                @if($post->published_at)
                    <time datetime="{{ $post->published_at->toDateString() }}">
                        {{ $post->published_at->format('d M Y') }}
                    </time>
                @endif
            </div>
        </header>

        @if($post->excerpt)
            <p style="font-size:1.1rem;color:#4b5563;margin:0 0 2rem;line-height:1.7;">
                {{ $post->excerpt }}
            </p>
        @endif

        {!! $renderedBlocks !!}
    </article>
@endsection
