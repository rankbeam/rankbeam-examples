@php
    // Resolve once: the <html lang> needs the page's resolved locale, and the
    // head needs the rendered tags. Both come from the same SEOData.
    $seo = isset($model) ? \Rankbeam\Examples\ExampleSeo::resolved($model) : null;
    $lang = $seo && $seo->locale
        ? str_replace('_', '-', $seo->locale)
        : str_replace('_', '-', app()->getLocale());
@endphp
<!DOCTYPE html>
<html lang="{{ $lang }}">
<head>
    {{-- App-owned head elements. <meta charset> must precede any non-ASCII
         metadata (Rendering Contract §6). --}}
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    {{-- App-owned tag the renderer must never touch (tag-ownership proof). --}}
    <meta name="app-sentinel" content="rankbeam-examples">

    {{--
        Stock one-liner from the Blade guide (resolves WITHOUT hreflang):
            @seo($model)
        This app renders through the same TagRenderer::render() recipe but feeds it
        SEOData augmented with per-page hreflang (see ExampleSeo / matrix
        README), so this server-rendered <head> carries the full contract,
        hreflang included.
    --}}
    @isset($seo)
        {!! app(\Rankbeam\Seo\Services\TagRenderer::class)->render($seo) !!}
    @endisset
</head>
<body>
    <header>
        <nav aria-label="Contract pages">
            <a href="{{ route('home') }}" data-testid="nav-home">Home</a>
            @foreach (\Rankbeam\Examples\Fixtures::pages() as $p)
                <a href="{{ url($p['path']) }}" data-testid="nav-{{ $p['key'] }}">{{ $p['nav_label'] }}</a>
            @endforeach
        </nav>
    </header>

    <main>
        @yield('body')
    </main>
</body>
</html>
