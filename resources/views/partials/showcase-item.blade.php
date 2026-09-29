@props(['project', 'slide' => 'left'])

<article class="showcase-item reveal" id="{{ $project->anchor() }}">
    <div class="showcase-item__media" data-media-reveal data-tilt>
        @if ($project->coverImageUrl())
            <img src="{{ $project->coverImageUrl() }}"
                 alt="{{ $project->title }}"
                 loading="lazy"
                 decoding="async"
                 width="1600"
                 height="1200">
        @else
            <div class="showcase-item__placeholder" aria-hidden="true"></div>
        @endif

        @if ($project->is_featured)
            <span class="showcase-item__badge">{{ setting('projects.featured_badge', 'Featured') }}</span>
        @endif
    </div>

    <div class="showcase-item__body" data-slide="{{ $slide }}">
        <p class="showcase-item__meta">
            {{ collect([$project->client_role, $project->client_name, $project->yearLabel()])->filter()->join(' · ') }}
        </p>

        <h3 class="showcase-item__title" data-reveal-words>{{ $project->title }}</h3>

        <div class="showcase-item__prose">
            @foreach ($project->descriptionParagraphs() as $paragraph)
                <p>{{ $paragraph }}</p>
            @endforeach
        </div>

        @if ($tech = $project->technologyList())
            <div class="showcase-item__tags">
                @foreach ($tech as $item)
                    <span class="pill">{{ $item }}</span>
                @endforeach
            </div>
        @endif

        @if ($project->hasLinks())
            <div class="showcase-item__links">
                @if ($project->project_url)
                    <a href="{{ $project->project_url }}" target="_blank" rel="noopener noreferrer">
                        {{ setting('projects.visit_cta', 'Visit project') }}
                        @include('partials.icon', ['name' => 'arrow-up-right', 'class' => 'btn__icon'])
                    </a>
                @endif

                @if ($project->github_url)
                    <a href="{{ $project->github_url }}" target="_blank" rel="noopener noreferrer">
                        @include('partials.icon', ['name' => 'github', 'class' => 'btn__icon'])
                        {{ setting('projects.source_cta', 'View source') }}
                    </a>
                @endif
            </div>
        @endif
    </div>

    @if ($project->images->isNotEmpty())
        <div class="showcase-item__gallery">
            @foreach ($project->images as $image)
                <img src="{{ $image->url() }}"
                     alt="{{ $image->alt ?: $project->title }}"
                     loading="lazy"
                     decoding="async">
            @endforeach
        </div>
    @endif
</article>
