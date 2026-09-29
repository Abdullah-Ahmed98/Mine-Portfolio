@php
    // The words are stored as a single comma separated setting, so the admin
    // can edit the whole strip in one field.
    $words = collect(preg_split('/[,·|]/u', (string) setting('marquee.words', 'Design')))
        ->map(fn ($word) => trim($word))
        ->filter()
        ->values();

    if ($words->isEmpty()) {
        return;
    }
@endphp

<div class="marquee" aria-hidden="true">
    <div class="marquee__track">
        {{-- Two identical groups so the -50% translate loops seamlessly. --}}
        @for ($copy = 0; $copy < 2; $copy++)
            <div class="marquee__group">
                @foreach ($words as $word)
                    <span class="marquee__word">{{ $word }}</span>
                    <span class="marquee__word marquee__star">✦</span>
                @endforeach
            </div>
        @endfor
    </div>
</div>
