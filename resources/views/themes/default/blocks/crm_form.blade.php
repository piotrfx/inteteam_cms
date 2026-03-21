@php
    $fields   = $crmData['fields'] ?? [];
    $formSlug = $data['form_slug'] ?? '';
    $title    = $crmData['title'] ?? $data['title'] ?? '';
    $submitLabel = $crmData['submit_label'] ?? 'Submit';
@endphp

@if(count($fields) > 0)
<section class="cms-crm-form" style="margin:2rem 0;">
    @if($title)
    <h3 style="margin:0 0 1rem;">{{ $title }}</h3>
    @endif

    <form
        method="POST"
        action="{{ route('crm.form.submit', ['company' => app('current_company')->slug ?? '', 'slug' => $formSlug]) }}"
        style="display:flex;flex-direction:column;gap:1rem;max-width:32rem;"
    >
        @csrf

        @foreach($fields as $field)
        @php
            $fieldId   = 'field_' . ($field['name'] ?? '');
            $required  = $field['required'] ?? false;
            $fieldType = $field['type'] ?? 'text';
        @endphp
        <div style="display:flex;flex-direction:column;gap:0.25rem;">
            <label for="{{ $fieldId }}" style="font-size:.875rem;font-weight:500;">
                {{ $field['label'] ?? $field['name'] ?? '' }}
                @if($required)<span style="color:#ef4444;" aria-hidden="true">*</span>@endif
            </label>

            @if($fieldType === 'textarea')
            <textarea
                id="{{ $fieldId }}"
                name="{{ $field['name'] ?? '' }}"
                rows="4"
                @if($required) required @endif
                style="padding:0.5rem;border:1px solid #d1d5db;border-radius:6px;font-size:.875rem;resize:vertical;"
            ></textarea>
            @elseif($fieldType === 'select' && !empty($field['options']))
            <select
                id="{{ $fieldId }}"
                name="{{ $field['name'] ?? '' }}"
                @if($required) required @endif
                style="padding:0.5rem;border:1px solid #d1d5db;border-radius:6px;font-size:.875rem;"
            >
                <option value="">Select…</option>
                @foreach($field['options'] as $opt)
                <option value="{{ $opt['value'] ?? $opt }}">{{ $opt['label'] ?? $opt }}</option>
                @endforeach
            </select>
            @else
            <input
                type="{{ $fieldType }}"
                id="{{ $fieldId }}"
                name="{{ $field['name'] ?? '' }}"
                @if($required) required @endif
                style="padding:0.5rem;border:1px solid #d1d5db;border-radius:6px;font-size:.875rem;"
            >
            @endif
        </div>
        @endforeach

        <button
            type="submit"
            style="align-self:flex-start;padding:0.5rem 1.5rem;background:var(--cms-primary,#2563eb);color:#fff;border:none;border-radius:6px;font-size:.875rem;cursor:pointer;"
        >
            {{ $submitLabel }}
        </button>
    </form>
</section>
@endif
