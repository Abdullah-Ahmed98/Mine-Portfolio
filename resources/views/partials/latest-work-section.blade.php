@php
    // Nothing on this section is hardcoded: the heading comes from settings and
    // every card is a CMS row. If no item is visible the whole section is
    // skipped, so the page and the navigation stay in step.
    $items = $items ?? collect();
@endphp

@if ($items->isNotEmpty())
    <section class="latest-work" id="latest-work">
        {{--
            Full-bleed on purpose. At 14vw the heading needs the entire viewport
            to sit on one line, so it cannot live inside a padded wrapper.
        --}}
        <h2 class="latest-work__heading">
            {{ setting('section.latest_work.title', 'Latest') }}<span class="latest-work__outline">&nbsp;{{ setting('section.latest_work.title_outline', 'work') }}</span>
        </h2>

        <div class="latest-work__grid">
            @foreach ($items as $item)
                @include('partials.latest-work-card', ['item' => $item])
            @endforeach
        </div>

        {{-- Only ever shown on a pointer device, and only while a tile is under
             the cursor. Carries the tile's title so the destination is named
             before the click. --}}
        <div class="latest-work__cursor" aria-hidden="true" data-work-cursor></div>
    </section>
@endif
