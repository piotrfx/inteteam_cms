@php
    $text  = $data['text'] ?? '';
    $url   = $data['url'] ?? '#';
    $style = $data['style'] ?? 'primary';

    $btnStyle = match($style) {
        'primary'   => 'background:var(--brand,#4f46e5);color:#fff;border:2px solid transparent;',
        'secondary' => 'background:#f3f4f6;color:#111;border:2px solid transparent;',
        'outline'   => 'background:transparent;color:var(--brand,#4f46e5);border:2px solid var(--brand,#4f46e5);',
        default     => 'background:var(--brand,#4f46e5);color:#fff;border:2px solid transparent;',
    };
@endphp
@if($text && $url)
    <div style="margin:1.5rem 0;">
        <a href="{{ $url }}"
           style="display:inline-block;padding:0.75rem 1.5rem;border-radius:0.5rem;font-weight:600;text-decoration:none;font-size:0.95rem;{{ $btnStyle }}">
            {{ $text }}
        </a>
    </div>
@endif
