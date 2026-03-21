@extends('themes.default.layouts.public')

@section('content')
    <h1 style="font-size:2rem;font-weight:800;margin:0 0 2rem;">Blog</h1>

    @if($posts->isEmpty())
        <p style="color:#6b7280;">No posts published yet.</p>
    @else
        <div style="display:grid;gap:2rem;">
            @foreach($posts as $post)
                <article style="border:1px solid #e5e7eb;border-radius:0.75rem;overflow:hidden;">
                    @if($post->featured_image_path)
                        <img src="{{ Storage::url($post->featured_image_path) }}"
                             alt="{{ $post->title }}"
                             style="width:100%;height:220px;object-fit:cover;">
                    @endif
                    <div style="padding:1.5rem;">
                        <h2 style="font-size:1.25rem;font-weight:700;margin:0 0 0.5rem;">
                            <a href="{{ url('/blog/' . $post->slug) }}"
                               style="text-decoration:none;color:inherit;">
                                {{ $post->title }}
                            </a>
                        </h2>
                        @if($post->excerpt)
                            <p style="color:#6b7280;margin:0 0 1rem;font-size:0.95rem;">
                                {{ $post->excerpt }}
                            </p>
                        @endif
                        <div style="font-size:0.8rem;color:#9ca3af;">
                            @if($post->published_at)
                                {{ $post->published_at->format('d M Y') }}
                            @endif
                        </div>
                    </div>
                </article>
            @endforeach
        </div>
    @endif
@endsection
