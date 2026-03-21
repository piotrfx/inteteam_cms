@php
    $items   = $crmData['items'] ?? $crmData ?? [];
    $layout  = $data['layout'] ?? 'grid';
    $columns = (int)($data['columns'] ?? 3);
@endphp

@if(count($items) > 0)
<section class="cms-gallery cms-gallery--{{ $layout }}" style="display:grid;grid-template-columns:repeat({{ $columns }},1fr);gap:0.75rem;margin:2rem 0;">
    @foreach($items as $item)
    <figure style="margin:0;border-radius:8px;overflow:hidden;aspect-ratio:1/1;">
        <img
            src="{{ $item['url'] ?? '' }}"
            alt="{{ $item['alt'] ?? '' }}"
            loading="lazy"
            style="width:100%;height:100%;object-fit:cover;"
        >
    </figure>
    @endforeach
</section>
@endif
