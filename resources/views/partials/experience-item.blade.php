@props(['experience'])

<article class="card tl-item reveal">
    <div class="tl-head">
        <h3 class="tl-role">{{ $experience->position }}</h3>
        @if ($range = $experience->dateRange())
            <span class="tl-date">{{ $range }}</span>
        @endif
    </div>

    <p class="tl-company">
        @if ($experience->company_url)
            <a href="{{ $experience->company_url }}" target="_blank" rel="noopener noreferrer">{{ $experience->company }}</a>
        @else
            {{ $experience->company }}
        @endif
        @if ($experience->location)
            <span class="muted"> — {{ $experience->location }}</span>
        @endif
        @if ($experience->is_current)
            <span class="pill pill--accent" style="margin-left: 8px;">Current</span>
        @endif
    </p>

    @if ($experience->description)
        <p class="muted">{{ $experience->description }}</p>
    @endif

    @if ($bullets = $experience->responsibilityList())
        <ul class="tl-list">
            @foreach ($bullets as $bullet)
                <li>{{ $bullet }}</li>
            @endforeach
        </ul>
    @endif

    @if ($tech = $experience->technologyList())
        <div class="tl-tags">
            @foreach ($tech as $item)
                <span class="pill">{{ $item }}</span>
            @endforeach
        </div>
    @endif
</article>
