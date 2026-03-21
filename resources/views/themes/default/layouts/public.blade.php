<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    {{-- SEO meta --}}
    <title>{{ $seo['title'] ?? $company->name }}</title>
    <meta name="description" content="{{ $seo['description'] ?? $company->seo_meta_description ?? '' }}">

    @if(!empty($seo['robots']))
        <meta name="robots" content="{{ $seo['robots'] }}">
    @endif

    {{-- Open Graph --}}
    <meta property="og:type"        content="{{ $seo['og_type'] ?? 'website' }}">
    <meta property="og:title"       content="{{ $seo['title'] ?? $company->name }}">
    <meta property="og:description" content="{{ $seo['description'] ?? $company->seo_meta_description ?? '' }}">
    <meta property="og:url"         content="{{ $seo['canonical'] ?? url()->current() }}">
    @if(!empty($seo['og_image']))
        <meta property="og:image" content="{{ $seo['og_image'] }}">
    @endif
    @if(!empty($company->seo_site_name))
        <meta property="og:site_name" content="{{ $company->seo_site_name }}">
    @endif

    {{-- Twitter Card --}}
    <meta name="twitter:card"        content="summary_large_image">
    @if(!empty($company->seo_twitter_handle))
        <meta name="twitter:site" content="{{ $company->seo_twitter_handle }}">
    @endif
    <meta name="twitter:title"       content="{{ $seo['title'] ?? $company->name }}">
    <meta name="twitter:description" content="{{ $seo['description'] ?? $company->seo_meta_description ?? '' }}">

    {{-- Canonical --}}
    @if(!empty($seo['canonical']))
        <link rel="canonical" href="{{ $seo['canonical'] }}">
    @endif

    {{-- Google verification --}}
    @if(!empty($company->seo_google_verification))
        <meta name="google-site-verification" content="{{ $company->seo_google_verification }}">
    @endif

    {{-- JSON-LD --}}
    @if(!empty($seo['json_ld']))
        <script type="application/ld+json">{!! $seo['json_ld'] !!}</script>
    @endif

    {{-- Brand colour --}}
    @if(!empty($company->primary_colour))
        <style>:root { --brand: {{ $company->primary_colour }}; }</style>
    @endif

    {{-- Favicon --}}
    @if(!empty($company->favicon_path))
        <link rel="icon" href="{{ Storage::url($company->favicon_path) }}">
    @endif

    <style>
        *, *::before, *::after { box-sizing: border-box; }
        body { margin: 0; font-family: system-ui, sans-serif; color: #111; background: #fff; line-height: 1.6; }
        .site-container { max-width: 1100px; margin: 0 auto; padding: 0 1.5rem; }
        a { color: var(--brand, #4f46e5); }
        img { max-width: 100%; height: auto; }
    </style>
</head>
<body>

    @include('themes.default.partials.header')

    <main class="site-container" style="padding-top:2rem;padding-bottom:4rem;">
        @yield('content')
    </main>

    @include('themes.default.partials.footer')

</body>
</html>
