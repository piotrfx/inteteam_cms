<footer style="border-top:1px solid #e5e7eb;background:#f9fafb;margin-top:4rem;">
    <div class="site-container" style="padding-top:2rem;padding-bottom:2rem;">
        @if(!empty($nav['footer']))
            <nav style="display:flex;flex-wrap:wrap;gap:1rem;margin-bottom:1rem;">
                @foreach($nav['footer'] as $item)
                    <a href="{{ $item['url'] }}"
                       target="{{ $item['target'] ?? '_self' }}"
                       style="text-decoration:none;font-size:0.85rem;color:#6b7280;">
                        {{ $item['label'] }}
                    </a>
                @endforeach
            </nav>
        @endif
        <p style="font-size:0.8rem;color:#9ca3af;margin:0;">
            &copy; {{ date('Y') }} {{ $company->name }}
        </p>
    </div>
</footer>
