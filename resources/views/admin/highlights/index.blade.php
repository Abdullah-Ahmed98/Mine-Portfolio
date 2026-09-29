<x-layouts.admin title="Highlights">
    <div class="topbar">
        <div>
            <h1>Highlights</h1>
            <p>Short cards under the hero — focus, location, key strengths and anything else.</p>
        </div>

        <div class="topbar__actions">
            <a class="btn btn--primary btn--sm" href="{{ route('admin.highlights.create') }}">Add highlight</a>
        </div>
    </div>

    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th style="width: 46px;">Order</th>
                    <th style="width: 30%;">Title</th>
                    <th>Text</th>
                    <th style="width: 190px;"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($highlights as $index => $highlight)
                    <tr>
                        <td>
                            @include('admin.partials.reorder', [
                                'type' => 'profile-highlights',
                                'id' => $highlight->id,
                                'first' => $index === 0,
                                'last' => $index === $highlights->count() - 1,
                            ])
                        </td>
                        <td class="table__title">{{ $highlight->title }}</td>
                        <td>{{ $highlight->text }}</td>
                        <td>
                            <div class="table__actions">
                                <a class="icon-btn" href="{{ route('admin.highlights.edit', $highlight) }}"
                                   title="Edit" aria-label="Edit">
                                    @include('partials.icon', ['name' => 'edit'])
                                </a>
                                <form method="POST" action="{{ route('admin.highlights.destroy', $highlight) }}"
                                      onsubmit="return confirm('Remove this highlight?');">
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
                        <td colspan="4" class="empty">No highlights yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-layouts.admin>
