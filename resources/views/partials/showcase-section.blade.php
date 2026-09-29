@props(['category', 'position' => 1])

{{--
    One showcase section per project category. Adding, renaming, reordering or
    deleting a category in the CMS adds, renames, reorders or removes a whole
    section here, with no code change.
--}}
<section class="section showcase" id="work-{{ $category->slug }}">
    <div class="wrap">
        <div class="showcase__head">
            <p class="eyebrow">{{ str_pad($position, 2, '0', STR_PAD_LEFT) }}</p>
            <h2 class="title showcase__title" data-drift data-reveal-words>{{ $category->name }}</h2>

            @if ($category->description)
                <p class="lead showcase__lead">{{ $category->description }}</p>
            @endif
        </div>

        <div class="showcase__list">
            @foreach ($category->projects as $index => $project)
                @include('partials.showcase-item', [
                    'project' => $project,
                    // The body sits opposite the media, so it travels in from the
                    // far side and the two meet in the middle.
                    'slide' => $index % 2 === 0 ? 'left' : 'right',
                ])
            @endforeach
        </div>
    </div>
</section>
