<!doctype html>
<html lang="{{ str_replace('_', '-', $locale) }}">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">{!! $tags !!}</head>
<body><main><h1>{{ $post->title }}</h1><p>{{ $post->content }}</p></main></body>
</html>
