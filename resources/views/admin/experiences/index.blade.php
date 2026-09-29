<x-layouts.admin title="Experience">
    <div class="topbar">
        <div>
            <h1>Experience</h1>
            <p>The timeline on the home and experience pages. Newest roles are listed first.</p>
        </div>

        <div class="topbar__actions">
            <a class="btn btn--primary btn--sm" href="{{ route('admin.experiences.create') }}">Add role</a>
        </div>
    </div>

    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th style="width: 46px;">Order</th>
                    <th>Position</th>
                    <th>Company</th>
                    <th style="width: 150px;">Dates</th>
                    <th style="width: 100px;">Bullets</th>
                    <th style="width: 190px;"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($experiences as $index => $experience)
                    <tr>
                        <td>
                            @include('admin.partials.reorder', [
                                'type' => 'experiences',
                                'id' => $experience->id,
                                'first' => $index === 0,
                                'last' => $index === $experiences->count() - 1,
                            ])
                        </td>
                        <td>
                            <span class="table__title">{{ $experience->position }}</span>
                            @if ($experience->is_current)
                                <span class="badge badge--accent" style="margin-left: 6px;">current</span>
                            @endif
                            @if ($experience->location)
                                <p class="table__sub">{{ $experience->location }}</p>
                            @endif
                        </td>
                        <td>
                            {{ $experience->company }}
                            @if ($experience->company_url)
                                <a class="table__sub" href="{{ $experience->company_url }}"
                                   target="_blank" rel="noopener noreferrer">visit site</a>
                            @endif
                        </td>
                        <td class="table__sub">{{ $experience->dateRange() ?: '—' }}</td>
                        <td><span class="badge">{{ count($experience->responsibilities ?? []) }}</span></td>
                        <td>
                            <div class="table__actions">
                                <a class="icon-btn" href="{{ route('admin.experiences.edit', $experience) }}"
                                   title="Edit" aria-label="Edit">
                                    @include('partials.icon', ['name' => 'edit'])
                                </a>
                                <form method="POST" action="{{ route('admin.experiences.destroy', $experience) }}"
                                      onsubmit="return confirm('Remove this role?');">
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
                        <td colspan="6" class="empty">No experience entries yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-layouts.admin>
