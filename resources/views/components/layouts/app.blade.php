<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('partials.head')

    @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/js/motion.js'])

    @stack('head')
</head>
<body>
    @include('partials.preloader')

    <a class="skip-link" href="#main">Skip to content</a>

    @include('partials.header')

    <main id="main">
        @yield('content')
    </main>

    @include('partials.footer')

    @if (($profile ?? null)?->full_name)
        <script type="application/ld+json">{!! \App\Support\JsonLd::graph($profile, $socialLinks ?? collect(), $showcaseProjects ?? collect()) !!}</script>
    @endif

    @stack('scripts')
</body>
</html>
