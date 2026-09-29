<x-layouts.admin title="Education">
    <div class="topbar">
        <div>
            <h1>Education &amp; achievements</h1>
            <p>Degrees, certifications and competition results.</p>
        </div>

        <div class="topbar__actions">
            <a class="btn btn--primary btn--sm" href="{{ route('admin.education.create') }}">Add entry</a>
        </div>
    </div>

    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th style="width: 46px;">Order</th>
                    <th>Title</th>
                    <th>Institution</th>
                    <th style="width: 160px;">Dates</th>
                    <th style="width: 190px;"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($entries as $index => $entry)
                    <tr>
                        <td>
                            @include('admin.partials.reorder', [
                                'type' => 'education',
                                'id' => $entry->id,
                                'first' => $index === 0,
                                'last' => $index === $entries->count() - 1,
                            ])
                        </td>
                        <td>
                            <span class="table__title">{{ $entry->title }}</span>
                            @if ($entry->description)
                                <p class="table__sub">{{ Str::limit($entry->description, 70) }}</p>
                            @endif
                        </td>
                        <td>{{ $entry->institution ?: '—' }}</td>
                        <td class="table__sub">{{ $entry->dateRange() ?: '—' }}</td>
                        <td>
                            <div class="table__actions">
                                <a class="icon-btn" href="{{ route('admin.education.edit', $entry) }}"
                                   title="Edit" aria-label="Edit">
                                    @include('partials.icon', ['name' => 'edit'])
                                </a>
                                <form method="POST" action="{{ route('admin.education.destroy', $entry) }}"
                                      onsubmit="return confirm('Remove this entry?');">
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
                        <td colspan="5" class="empty">No education entries yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-layouts.admin>
