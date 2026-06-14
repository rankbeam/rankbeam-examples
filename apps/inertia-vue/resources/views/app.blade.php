<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', $page['props']['locale'] ?? app()->getLocale()) }}">
<head>
    {{-- App-owned: charset must precede any non-ASCII metadata (Contract §6). --}}
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    {{-- App-owned tag Inertia must never touch (tag-ownership proof). --}}
    <meta name="app-sentinel" content="rankbeam-examples">

    {{-- Inertia renders the page <Head> here. With SSR enabled this is in the
         raw HTTP HTML (crawler-visible); without SSR it is injected client-side
         (documented as non-compliant — Contract §5). --}}
    @php
        ob_start();
    @endphp
    @inertiaHead
    @php
        echo str_replace('<title inertia>', '<title>', ob_get_clean());
    @endphp
    @php
        $schema = $page['props']['schema'] ?? [];
        $schemaUrl = collect($page['props']['seo']['link'] ?? [])
            ->firstWhere('rel', 'canonical')['href'] ?? ($page['url'] ?? '');
    @endphp
    @foreach ($schema as $entry)
        <script type="application/ld+json" data-seo-schema data-seo-url="{{ $schemaUrl }}">{!! $entry['innerHTML'] !!}</script>
    @endforeach
    <script>document.head.querySelector('title')?.setAttribute('inertia', '')</script>
    @vite(['resources/js/app.js'])
</head>
<body>
    @inertia
</body>
</html>
