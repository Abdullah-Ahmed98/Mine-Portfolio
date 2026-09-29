@props(['tag' => null, 'title' => null, 'lead' => null])

{{--
    A section heading: eyebrow, title and optional intro.

    This is a component rather than an @include'd partial on purpose. An include
    inherits every variable in the caller's scope, so a $lead set anywhere
    earlier in the page leaked into every heading below it and each one repeated
    the same sentence. A component only ever sees the props it was given.
--}}

@if ($tag || $title || $lead)
    <div class="section-head reveal">
        @if ($tag)
            <p class="eyebrow">{{ $tag }}</p>
        @endif

        @if ($title)
            {{-- data-drift lets the heading travel at a different rate to the page. --}}
            <h2 class="title section-head__title" data-drift data-reveal-words>{{ $title }}</h2>
        @endif

        @if ($lead)
            <p class="lead section-head__lead">{{ $lead }}</p>
        @endif
    </div>
@endif
