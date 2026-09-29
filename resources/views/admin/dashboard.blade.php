<x-layouts.admin title="Dashboard">
    <div class="topbar">
        <div>
            <h1>Dashboard</h1>
            <p>Everything on the public site is edited from here.</p>
        </div>

        <div class="topbar__actions">
            <a class="btn btn--ghost btn--sm" href="{{ route('admin.profile.edit') }}">Edit profile</a>
            <a class="btn btn--primary btn--sm" href="{{ route('admin.projects.create') }}">Add project</a>
        </div>
    </div>

    <div class="stat-grid">
        @foreach ($counts as $label => $value)
            <div class="stat">
                <p class="stat__value">{{ $value }}</p>
                <p class="stat__label">{{ $label }}</p>
            </div>
        @endforeach
    </div>

    <div class="split">
        <div>
            <div class="card">
                <p class="card__title">Latest messages</p>
                <p class="card__hint">Submissions from the contact form.</p>

                @forelse ($recentMessages as $message)
                    <a class="msg{{ $message->is_read ? '' : ' is-unread' }}"
                       href="{{ route('admin.messages.show', $message) }}">
                        <div class="msg__top">
                            <span class="msg__from">{{ $message->name }}</span>
                            <span class="msg__date">{{ $message->created_at->diffForHumans() }}</span>
                        </div>
                        <p class="msg__preview">{{ Str::limit($message->message, 110) }}</p>
                    </a>
                @empty
                    <p class="empty">No messages yet.</p>
                @endforelse

                <div class="form__actions">
                    <a class="btn btn--ghost btn--sm" href="{{ route('admin.messages.index') }}">Open inbox</a>
                </div>
            </div>
        </div>

        <div>
            <div class="card">
                <p class="card__title">Shortcuts</p>
                <p class="card__hint">Jump straight to a section.</p>

                <div class="form">
                    <a class="btn btn--ghost btn--sm" href="{{ route('admin.experiences.index') }}">Experience</a>
                    <a class="btn btn--ghost btn--sm" href="{{ route('admin.skills.index') }}">Skills</a>
                    <a class="btn btn--ghost btn--sm" href="{{ route('admin.latest-work.index') }}">Latest work</a>
                    <a class="btn btn--ghost btn--sm" href="{{ route('admin.projects.index') }}">Projects</a>
                    <a class="btn btn--ghost btn--sm" href="{{ route('admin.highlights.index') }}">Highlights</a>
                    <a class="btn btn--ghost btn--sm" href="{{ route('admin.social-links.index') }}">Social links</a>
                    <a class="btn btn--ghost btn--sm" href="{{ route('admin.settings.index') }}">Settings</a>
                </div>
            </div>

            <div class="card">
                <p class="card__title">Current profile</p>

                <p class="table__title">{{ $profile->full_name }}</p>
                <p class="table__sub">{{ $profile->title }}</p>

                <ul class="form" style="margin-top: 14px; gap: 8px;">
                    <li class="tag-preview">
                        <span>Photo: {{ $profile->profile_image ? 'set' : 'missing' }}</span>
                        <span>Resume: {{ $profile->hasCv() ? 'set' : 'missing' }}</span>
                    </li>
                </ul>

                <div class="form__actions">
                    <a class="btn btn--ghost btn--sm" href="{{ route('admin.profile.edit') }}">Edit</a>
                </div>
            </div>
        </div>
    </div>
</x-layouts.admin>
