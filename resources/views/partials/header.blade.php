@php
    // $navItems comes from SinglePage::navigation(), so the header and the page
    // body can never disagree about which sections exist or what order they are in.
    $navItems = $navItems ?? [];
@endphp

<header class="header" data-header>
    {{-- How far through the single page the visitor is. Driven by scroll. --}}
    <div class="scroll-progress" aria-hidden="true"><span data-scroll-progress></span></div>

    <div class="header__inner">
        <a class="header__name" href="#top">{{ $profile->full_name ?? 'Portfolio' }}</a>

        <nav class="header__nav" aria-label="Primary">
            <ul class="nav-list">
                @foreach ($navItems as $item)
                    <li>
                        <a href="#{{ $item['anchor'] }}" data-nav-anchor="{{ $item['anchor'] }}">{{ $item['label'] }}</a>
                    </li>
                @endforeach
            </ul>
        </nav>

        @if ($profile->hasCv())
            <div class="header__cta">
                <a class="btn btn--ghost btn--sm" href="{{ route('resume') }}" target="_blank" rel="noopener">
                    @include('partials.icon', ['name' => 'download', 'class' => 'btn__icon'])
                    {{ setting('nav.resume', 'Get My Resume') }}
                </a>
            </div>
        @endif

        <button class="nav-toggle"
                type="button"
                data-nav-toggle
                aria-label="{{ setting('nav.menu_open', 'Open menu') }}"
                aria-expanded="false"
                aria-controls="mobile-menu">
            <span></span><span></span><span></span>
        </button>
    </div>
</header>

<div class="mobile-menu" id="mobile-menu" data-mobile-menu>
    @foreach ($navItems as $index => $item)
        <a href="#{{ $item['anchor'] }}" data-nav-anchor="{{ $item['anchor'] }}">
            {{ $item['label'] }}
            <span>{{ str_pad($index + 1, 2, '0', STR_PAD_LEFT) }}</span>
        </a>
    @endforeach

    @if ($profile->hasCv())
        <a href="{{ route('resume') }}" target="_blank" rel="noopener">
            {{ setting('nav.resume', 'Get My Resume') }}
            <span>PDF</span>
        </a>
    @endif

    <div class="mobile-menu__footer">
        @include('partials.social-links', ['socialLinks' => $socialLinks ?? collect(), 'iconOnly' => false])
    </div>
</div>
