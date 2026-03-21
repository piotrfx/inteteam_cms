@php $html = $data['html'] ?? ''; @endphp
@if($html)
    <div class="prose" style="margin:1rem 0;max-width:65ch;">
        {!! $html !!}
    </div>
@endif
