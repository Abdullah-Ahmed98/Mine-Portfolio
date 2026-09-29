@php
    $renderable = ($socialLinks ?? collect())->filter(fn ($link) => $link->isRenderable());
    $iconOnly = $iconOnly ?? false;
@endphp

@if ($renderable->isNotEmpty())
    <ul class="socials{{ $iconOnly ? ' footer-socials' : '' }}">
        @foreach ($renderable as $link)
            @php $external = ! str_starts_with((string) $link->resolvedUrl(), 'mailto:') && ! str_starts_with((string) $link->resolvedUrl(), 'tel:'); @endphp
            <li>
                <a class="social-link{{ $iconOnly ? ' social-link--icon' : '' }}"
                   href="{{ $link->resolvedUrl() }}"
                   @if ($external) target="_blank" rel="noopener noreferrer" @endif
                   aria-label="{{ $link->label ?: \App\Models\SocialLink::platforms()[$link->platform] ?? $link->platform }}">
                    @include('partials.icon', ['name' => $link->iconKey()])
                    @unless ($iconOnly)
                        <span>{{ $link->label ?: (\App\Models\SocialLink::platforms()[$link->platform] ?? $link->platform) }}</span>
                    @endunless
                </a>
            </li>
        @endforeach
    </ul>
@endif
