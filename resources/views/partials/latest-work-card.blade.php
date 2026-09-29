@php
    // A tile with somewhere to go is a link; one without is not, so it stays a
    // plain box rather than a link that goes nowhere. The classes are identical
    // either way, so both look the same.
    $linked = $item->hasLinks();
    $tag = $linked ? 'a' : 'div';

    // The cover leads, then any gallery shots, all in the order the admin set.
    $shots = collect($item->coverImageUrl()
        ? [['url' => $item->coverImageUrl(), 'alt' => $item->title]]
        : [])
        ->merge($item->images->map(fn ($image) => [
            'url' => $image->url(),
            'alt' => $image->alt ?: $item->title,
        ]));
@endphp

<{{ $tag }} class="latest-work-card"
    @if ($linked)
        href="{{ $item->project_url }}" target="_blank" rel="noopener noreferrer"
    @endif
    data-work-card
    data-work-label="{{ $item->title }}">

    {{--
        The tile only has room for one image at a time, so a gallery is stacked
        into the same frame and cycled on hover rather than stacked into a strip
        below. Without a pointer the cover simply stays put, which is the right
        thing for a touch screen anyway.
    --}}
    <div class="latest-work-card__media" data-media-reveal @if ($shots->count() > 1) data-work-gallery @endif>
        @if ($shots->isEmpty())
            <div class="latest-work-card__placeholder" aria-hidden="true"></div>
        @else
            @foreach ($shots as $position => $shot)
                <img class="@if ($position === 0) latest-work-card__shot is-current @else latest-work-card__shot @endif"
                     src="{{ $shot['url'] }}"
                     alt="{{ $shot['alt'] }}"
                     loading="lazy"
                     decoding="async"
                     width="1600"
                     height="1450">
            @endforeach
        @endif
    </div>

    <div class="latest-work-card__scrim" aria-hidden="true"></div>

    @if ($item->is_featured || $shots->count() > 1)
        <div class="latest-work-card__flags">
            @if ($item->is_featured)
                <span class="latest-work-card__badge">{{ setting('projects.featured_badge', 'Featured') }}</span>
            @endif

            @if ($shots->count() > 1)
                {{-- The tile only shows one shot at a time, so the rest are
                     counted here rather than left undiscoverable. --}}
                <span class="latest-work-card__badge">{{ $shots->count() }} {{ str('image')->plural($shots->count()) }}</span>
            @endif
        </div>
    @endif

    <div class="latest-work-card__label">
        <h3 class="latest-work-card__title">{{ $item->title }}</h3>

        @if ($item->typeLabel())
            <p class="latest-work-card__cat">{{ $item->typeLabel() }}</p>
        @endif

        @if ($item->short_description)
            <p class="latest-work-card__prose">{{ $item->short_description }}</p>
        @endif

        @if ($tech = $item->technologyList())
            <div class="latest-work-card__tags">
                @foreach ($tech as $name)
                    <span class="pill">{{ $name }}</span>
                @endforeach
            </div>
        @endif
    </div>
</{{ $tag }}>
