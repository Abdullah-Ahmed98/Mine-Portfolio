<x-layouts.admin title="Latest work types">
    <div class="topbar">
        <div>
            <h1>Latest work types</h1>
            <p>Optional labels such as UI/UX or Development, shown under a card's title.</p>
        </div>

        <div class="topbar__actions">
            <a class="btn btn--primary btn--sm" href="{{ route('admin.latest-work-categories.create') }}">Add type</a>
        </div>
    </div>

    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th style="width: 46px;">Order</th>
                    <th>Name</th>
                    <th>Slug</th>
                    <th style="width: 100px;">Items</th>
                    <th style="width: 190px;"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($categories as $index => $category)
                    <tr>
                        <td>
                            @include('admin.partials.reorder', [
                                'type' => 'latest-work-categories',
                                'id' => $category->id,
                                'first' => $index === 0,
                                'last' => $index === $categories->count() - 1,
                            ])
                        </td>
                        <td class="table__title">{{ $category->name }}</td>
                        <td class="table__sub">{{ $category->slug }}</td>
                        <td><span class="badge">{{ $category->items_count }}</span></td>
                        <td>
                            <div class="table__actions">
                                <a class="icon-btn" href="{{ route('admin.latest-work-categories.edit', $category) }}"
                                   title="Edit" aria-label="Edit">
                                    @include('partials.icon', ['name' => 'edit'])
                                </a>
                                <form method="POST" action="{{ route('admin.latest-work-categories.destroy', $category) }}"
                                      onsubmit="return confirm('Remove this type? Its items are kept.');">
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
                        <td colspan="5" class="empty">No types yet. Items work without one.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-layouts.admin>
