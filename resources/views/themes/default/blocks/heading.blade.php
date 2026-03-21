@php
    $level = in_array((int)($data['level'] ?? 2), [1,2,3,4,5,6]) ? (int)$data['level'] : 2;
    $text  = $data['text'] ?? '';
    $sizes = [1=>'2rem', 2=>'1.5rem', 3=>'1.25rem', 4=>'1.1rem', 5=>'1rem', 6=>'0.9rem'];
    $style = "font-size:{$sizes[$level]};font-weight:700;line-height:1.25;margin:1.5rem 0 0.75rem;";
@endphp
@if($text)
    <h{{ $level }} style="{{ $style }}">{{ $text }}</h{{ $level }}>
@endif
