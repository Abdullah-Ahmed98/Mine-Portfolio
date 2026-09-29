@php
    $seo = $seo ?? [];
    $title = $seo['title'] ?? 'Portfolio';
    $description = $seo['description'] ?? '';
    $ogImage = $seo['image'] ?? null;
    $ogType = $seo['type'] ?? 'website';
    $keywords = $seo['keywords'] ?? null;
    $canonical = $seo['canonical'] ?? request()->url();
    $robots = $seo['robots'] ?? 'index, follow';
@endphp
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">

<title>{{ $title }}</title>
<meta name="description" content="{{ $description }}">
<meta name="robots" content="{{ $robots }}">
@if ($keywords)
    <meta name="keywords" content="{{ $keywords }}">
@endif
<link rel="canonical" href="{{ $canonical }}">

<meta property="og:site_name" content="{{ config('app.name') }}">
<meta property="og:type" content="{{ $ogType }}">
<meta property="og:title" content="{{ $title }}">
<meta property="og:description" content="{{ $description }}">
<meta property="og:url" content="{{ $canonical }}">
@if ($ogImage)
    <meta property="og:image" content="{{ \Illuminate\Support\Str::startsWith($ogImage, 'http') ? $ogImage : asset($ogImage) }}">
    <meta property="og:image:alt" content="{{ $title }}">
@endif
<meta name="twitter:card" content="{{ $ogImage ? 'summary_large_image' : 'summary' }}">
<meta name="twitter:title" content="{{ $title }}">
<meta name="twitter:description" content="{{ $description }}">
@if ($ogImage)
    <meta name="twitter:image" content="{{ \Illuminate\Support\Str::startsWith($ogImage, 'http') ? $ogImage : asset($ogImage) }}">
@endif

<meta name="theme-color" content="#000000">
<link rel="alternate" type="application/rss+xml" href="{{ route('sitemap') }}" title="Sitemap">

{{--
    Marks the document as scripted before the first paint. The animation
    stylesheet only ever hides content behind this class, so if the bundle never
    arrives the page is simply visible rather than stuck mid-animation.
--}}
<script>document.documentElement.classList.add('js');</script>
