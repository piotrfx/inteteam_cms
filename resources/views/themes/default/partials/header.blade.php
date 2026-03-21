<header style="border-bottom:1px solid #e5e7eb;background:#fff;">
    <div class="site-container" style="display:flex;align-items:center;justify-content:space-between;height:64px;">
        <a href="/" style="font-weight:700;font-size:1.125rem;text-decoration:none;color:inherit;">
            @if(!empty($company->logo_path))
                <img src="{{ Storage::url($company->logo_path) }}"
                     alt="{{ $company->name }}"
                     style="height:40px;width:auto;object-fit:contain;">
            @else
                {{ $company->name }}
            @endif
        </a>

        @if(!empty($nav['header']))
            <nav style="display:flex;gap:1.5rem;">
                @foreach($nav['header'] as $item)
                    <a href="{{ $item['url'] }}"
                       target="{{ $item['target'] ?? '_self' }}"
                       style="text-decoration:none;font-size:0.9rem;color:#374151;">
                        {{ $item['label'] }}
                    </a>
                @endforeach
            </nav>
        @endif
    </div>
</header>
