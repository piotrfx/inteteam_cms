@php
    $updates = $crmData['updates'] ?? $crmData ?? [];
@endphp

@if(count($updates) > 0)
<section class="cms-business-updates" style="margin:2rem 0;display:flex;flex-direction:column;gap:1.5rem;">
    @foreach($updates as $update)
    <article style="border-left:3px solid var(--cms-primary,#2563eb);padding-left:1rem;">
        @if(!empty($update['published_at']))
        <time style="font-size:.75rem;color:#9ca3af;display:block;margin-bottom:0.25rem;">
            {{ \Carbon\Carbon::parse($update['published_at'])->format('j F Y') }}
        </time>
        @endif
        @if(!empty($update['title']))
        <h4 style="margin:0 0 0.5rem;font-size:1rem;font-weight:600;">{{ $update['title'] }}</h4>
        @endif
        @if(!empty($update['body']))
        <p style="margin:0;font-size:.875rem;color:#374151;">{{ Str::limit($update['body'], 200) }}</p>
        @endif
        @if(!empty($update['url']))
        <a href="{{ $update['url'] }}" style="display:inline-block;margin-top:0.5rem;font-size:.875rem;color:var(--cms-primary,#2563eb);">
            Read more &rsaquo;
        </a>
        @endif
    </article>
    @endforeach
</section>
@endif
