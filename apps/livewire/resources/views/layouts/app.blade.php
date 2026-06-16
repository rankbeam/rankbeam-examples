@php
    // Resolve once for the <html lang> and the head tags.
    $seo = isset($seoModel) ? \Rankbeam\Examples\ExampleSeo::resolved($seoModel) : null;
    $lang = $seo && $seo->locale
        ? str_replace('_', '-', $seo->locale)
        : str_replace('_', '-', app()->getLocale());
@endphp
<!DOCTYPE html>
<html lang="{{ $lang }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    {{-- App-owned tag the renderer must never touch (tag-ownership proof). --}}
    <meta name="app-sentinel" content="rankbeam-examples">

    {{--
        Stock one-liner from the Livewire guide (no hreflang):
            @seo($seoModel ?? null)
        This app renders through TagRenderer::render() with hreflang-augmented SEOData.
    --}}
    @isset($seo)
        {!! app(\Rankbeam\Seo\Services\TagRenderer::class)->render($seo) !!}
    @endisset

    @livewireStyles
</head>
<body>
    <header>
        <nav aria-label="Contract pages">
            <a href="{{ route('home') }}" wire:navigate data-testid="nav-home">Home</a>
            @foreach (\Rankbeam\Examples\Fixtures::pages() as $p)
                <a href="{{ url($p['path']) }}" wire:navigate data-testid="nav-{{ $p['key'] }}">{{ $p['nav_label'] }}</a>
            @endforeach
        </nav>
    </header>

    {{ $slot }}

    @livewireScripts

    {{--
        JSON-LD teardown on SPA navigation. Livewire treats <script> as a
        non-removable asset, so JSON-LD from every visited page accumulates in
        the <head>. This removes any schema whose data-seo-url is not the page
        we are now on — which also clears a lone stale schema when navigating to
        a page that has NONE (the case the original docs snippet's `length < 2`
        early-return missed). See docs/guide/livewire.md.
    --}}
    <script>
        document.addEventListener('livewire:navigated', () => {
            const here = window.location.href.split('#')[0].split('?')[0]
            const scripts = [...document.querySelectorAll('script[data-seo-schema]')]
            // Keep only the LAST schema for the current page; remove stale
            // (other-URL) schema AND same-URL duplicates Livewire re-adds when a
            // page is revisited. Iterate from the end so the freshest is kept.
            let kept = false
            for (let i = scripts.length - 1; i >= 0; i--) {
                const url = (scripts[i].getAttribute('data-seo-url') || '').split('?')[0]
                if (url === here && !kept) { kept = true; continue }
                scripts[i].remove()
            }
        })
    </script>
</body>
</html>
