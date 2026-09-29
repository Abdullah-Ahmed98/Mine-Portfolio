<x-layouts.admin title="Social links">
    <div class="topbar">
        <div>
            <h1>Social links</h1>
            <p>A link with no URL is never shown on the site — that is how you keep a platform switched off.</p>
        </div>

        <div class="topbar__actions">
            <a class="btn btn--primary btn--sm" href="{{ route('admin.social-links.create') }}">Add link</a>
        </div>
    </div>

    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th style="width: 46px;">Order</th>
                    <th>Platform</th>
                    <th>Label</th>
                    <th>URL</th>
                    <th style="width: 96px;">On site</th>
                    <th style="width: 190px;"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($socialLinks as $index => $link)
                    <tr>
                        <td>
                            @include('admin.partials.reorder', [
                                'type' => 'social-links',
                                'id' => $link->id,
                                'first' => $index === 0,
                                'last' => $index === $socialLinks->count() - 1,
                            ])
                        </td>
                        <td>
                            <span class="table__title">{{ \App\Models\SocialLink::platforms()[$link->platform] ?? $link->platform }}</span>
                            <p class="table__sub">{{ $link->platform }}</p>
                        </td>
                        <td>{{ $link->label ?: '—' }}</td>
                        <td>
                            @if ($link->hasDestination())
                                <span class="table__sub">{{ Str::limit($link->url, 46) }}</span>
                            @else
                                <span class="badge badge--off">empty — hidden</span>
                            @endif
                        </td>
                        <td>
                            @if (! $link->isRenderable())
                                <span class="badge badge--off">hidden</span>
                            @else
                                <span class="badge badge--accent">visible</span>
                            @endif
                        </td>
                        <td>
                            <div class="table__actions">
                                <a class="icon-btn" href="{{ route('admin.social-links.edit', $link) }}"
                                   title="Edit" aria-label="Edit">
                                    @include('partials.icon', ['name' => 'edit'])
                                </a>
                                <form method="POST" action="{{ route('admin.social-links.destroy', $link) }}"
                                      onsubmit="return confirm('Remove this link?');">
                                    @csrf
                                    @method('DELETE')
                                    <button class="icon-btn icon-btn--danger" type="submit"
                                            title="Delete" aria-label="Delete">
                                        @include('partials.icon', ['name' => 'trash'])
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="empty">No social links yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-layouts.admin>
