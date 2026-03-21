@php
    $src     = $data['src'] ?? '';
    $alt     = $data['alt'] ?? '';
    $caption = $data['caption'] ?? '';
@endphp
@if($src)
    <figure style="margin:1.5rem 0;">
        <img src="{{ $src }}" alt="{{ e($alt) }}" style="width:100%;border-radius:0.5rem;">
        @if($caption)
            <figcaption style="margin-top:0.5rem;font-size:0.85rem;color:#6b7280;text-align:center;">
                {{ $caption }}
            </figcaption>
        @endif
    </figure>
@endif
