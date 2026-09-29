@php
    $nav = [
        ['label' => 'Dashboard', 'route' => 'admin.dashboard', 'icon' => 'arrow-right'],
        ['label' => 'Profile', 'route' => 'admin.profile.edit', 'icon' => 'edit'],
        ['label' => 'Messages', 'route' => 'admin.messages.index', 'icon' => 'email', 'badge' => \App\Models\ContactMessage::query()->unread()->count()],
    ];

    $content = [
        'Work' => [
            ['label' => 'Latest work', 'route' => 'admin.latest-work.index', 'count' => \App\Models\LatestWorkItem::query()->count()],
            ['label' => 'Latest work types', 'route' => 'admin.latest-work-categories.index', 'count' => \App\Models\LatestWorkCategory::query()->count()],
            ['label' => 'Projects', 'route' => 'admin.projects.index', 'count' => \App\Models\Project::query()->count()],
            ['label' => 'Categories', 'route' => 'admin.project-categories.index', 'count' => \App\Models\ProjectCategory::query()->count()],
        ],
        'Resume content' => [
            ['label' => 'Experience', 'route' => 'admin.experiences.index', 'count' => \App\Models\Experience::query()->count()],
            ['label' => 'Education', 'route' => 'admin.education.index', 'count' => \App\Models\EducationEntry::query()->count()],
            ['label' => 'Skills', 'route' => 'admin.skills.index', 'count' => \App\Models\Skill::query()->count()],
            ['label' => 'Skill groups', 'route' => 'admin.skill-categories.index', 'count' => \App\Models\SkillCategory::query()->count()],
        ],
        'Site' => [
            ['label' => 'Highlights', 'route' => 'admin.highlights.index', 'count' => \App\Models\ProfileHighlight::query()->count()],
            ['label' => 'Social links', 'route' => 'admin.social-links.index', 'count' => \App\Models\SocialLink::query()->count()],
            ['label' => 'Settings', 'route' => 'admin.settings.index'],
        ],
    ];
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">

    <title>{{ $title ?? 'Admin' }} — {{ setting('seo.site_name', config('app.name')) }}</title>

    @vite(['resources/css/admin.css'])
</head>
<body>
    <div class="admin">
        <aside class="sidebar">
            <a class="sidebar__brand" href="{{ route('admin.dashboard') }}">
                <span class="sidebar__mark">{{ Str::upper(Str::substr(setting('seo.site_name', 'P'), 0, 1)) }}</span>
                <span>{{ setting('seo.site_name', config('app.name')) }}</span>
            </a>

            <nav class="sidebar__nav" aria-label="Admin">
                @foreach ($nav as $item)
                    <a class="sidebar__link{{ request()->routeIs($item['route']) ? ' is-active' : '' }}"
                       href="{{ route($item['route']) }}">
                        {{ $item['label'] }}
                        @if (! empty($item['badge']))
                            <span class="sidebar__count">{{ $item['badge'] }}</span>
                        @endif
                    </a>
                @endforeach

                @foreach ($content as $group => $items)
                    <p class="sidebar__label">{{ $group }}</p>
                    @foreach ($items as $item)
                        <a class="sidebar__link{{ request()->routeIs($item['route']) ? ' is-active' : '' }}"
                           href="{{ route($item['route']) }}">
                            {{ $item['label'] }}
                            @if (isset($item['count']))
                                <span class="sidebar__count">{{ $item['count'] }}</span>
                            @endif
                        </a>
                    @endforeach
                @endforeach
            </nav>

            <div class="sidebar__foot">
                <a class="btn btn--ghost btn--sm" href="{{ route('home') }}" target="_blank" rel="noopener">
                    View site
                    @include('partials.icon', ['name' => 'arrow-up-right'])
                </a>

                <form method="POST" action="{{ route('admin.logout') }}">
                    @csrf
                    <button class="btn btn--ghost btn--sm" type="submit" style="width: 100%;">
                        Sign out
                    </button>
                </form>
            </div>
        </aside>

        <main class="admin__main">
            @if (session('status'))
                <div class="alert alert--ok">{{ session('status') }}</div>
            @endif

            {{ $slot }}
        </main>
    </div>
</body>
</html>
