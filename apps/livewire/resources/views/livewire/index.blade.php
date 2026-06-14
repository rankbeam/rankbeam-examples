<div>
    <h1 data-testid="page-title">Rankbeam SEO — Livewire reference app</h1>
    <p>Navigate with <code>wire:navigate</code> to exercise SPA head-merge + JSON-LD teardown.</p>
    <ul>
        @foreach ($pages as $p)
            <li><a href="{{ url($p['path']) }}" wire:navigate data-testid="link-{{ $p['key'] }}">{{ $p['nav_label'] }}</a></li>
        @endforeach
    </ul>
</div>
