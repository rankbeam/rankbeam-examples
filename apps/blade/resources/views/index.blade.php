@extends('layouts.app')

@section('body')
    <h1 data-testid="page-title">Rankbeam SEO — Blade reference app</h1>
    <p>The three contract pages:</p>
    <ul>
        @foreach ($pages as $p)
            <li><a href="{{ url($p['path']) }}" data-testid="link-{{ $p['key'] }}">{{ $p['nav_label'] }}</a></li>
        @endforeach
    </ul>
@endsection
