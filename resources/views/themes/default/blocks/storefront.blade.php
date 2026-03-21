@php
    $products   = $crmData['products'] ?? [];
    $categories = $crmData['categories'] ?? [];
    $config     = $crmData['config'] ?? [];
    $columns    = (int)($data['columns'] ?? 3);
    $showPrice  = $config['show_price'] ?? true;
@endphp

@if(count($products) > 0)
<section class="cms-storefront" style="margin:2rem 0;">
    @if(count($categories) > 1)
    <nav class="cms-storefront__categories" style="display:flex;flex-wrap:wrap;gap:0.5rem;margin-bottom:1.5rem;">
        @foreach($categories as $cat)
        <span style="padding:0.25rem 0.75rem;background:#f3f4f6;border-radius:999px;font-size:.875rem;">
            {{ $cat['name'] ?? '' }}
        </span>
        @endforeach
    </nav>
    @endif

    <div style="display:grid;grid-template-columns:repeat({{ $columns }},1fr);gap:1rem;">
        @foreach($products as $product)
        <article style="border:1px solid #e5e7eb;border-radius:8px;overflow:hidden;">
            @if(!empty($product['image_url']))
            <img
                src="{{ $product['image_url'] }}"
                alt="{{ $product['name'] ?? '' }}"
                loading="lazy"
                style="width:100%;aspect-ratio:4/3;object-fit:cover;"
            >
            @endif
            <div style="padding:0.75rem;">
                <p style="margin:0 0 0.25rem;font-weight:600;">{{ $product['name'] ?? '' }}</p>
                @if($showPrice && isset($product['price']))
                <p style="margin:0;color:#6b7280;font-size:.875rem;">£{{ number_format((float)$product['price'], 2) }}</p>
                @endif
                @if(!empty($product['url']))
                <a href="{{ $product['url'] }}" style="display:inline-block;margin-top:0.5rem;font-size:.875rem;color:var(--cms-primary,#2563eb);">
                    View &rsaquo;
                </a>
                @endif
            </div>
        </article>
        @endforeach
    </div>
</section>
@endif
