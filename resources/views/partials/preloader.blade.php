{{--
    The opening curtain.

    It is server rendered so it is painted on the very first frame, which means
    it must also be removable without JavaScript. Two things guarantee that: the
    `preloader-failsafe` keyframes below hide it on their own after a few
    seconds, and the <noscript> rule hides it immediately when scripting is off.
    JavaScript only ever takes the animation earlier than the failsafe would.
--}}
<div class="preloader" data-preloader role="status" aria-live="polite">
    <span class="visually-hidden">Loading the portfolio</span>

    <div class="preloader__inner">
        <p class="preloader__name">{{ $profile->full_name ?? 'Portfolio' }}</p>

        {{-- The digits come from the counter in CSS; the script writes them. --}}
        <p class="preloader__count" data-preloader-count aria-hidden="true"></p>
    </div>

    <div class="preloader__bar" aria-hidden="true"><span data-preloader-bar></span></div>

    <div class="preloader__panel preloader__panel--a" aria-hidden="true"></div>
    <div class="preloader__panel preloader__panel--b" aria-hidden="true"></div>
</div>
