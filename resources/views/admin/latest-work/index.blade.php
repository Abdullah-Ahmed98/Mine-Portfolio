<x-layouts.admin title="Latest Work">
    <div class="topbar">
        <div>
            <h1>Latest Work</h1>
            <p>These cards appear in the “Latest work” section of the home page, in this order.</p>
        </div>

        <div class="topbar__actions">
            <a class="btn btn--primary btn--sm" href="{{ route('admin.latest-work.create') }}">Add item</a>
        </div>
    </div>

    @if ($items->isEmpty())
        <div class="card">
            <p class="card__title">Nothing here yet</p>
            <p class="card__hint">
                The section only appears on the home page once there is at least one visible item, and nothing
                is ever hardcoded — add your own work and it shows up straight away.
            </p>
            <div class="form__actions">
                <a class="btn btn--primary" href="{{ route('admin.latest-work.create') }}">Add your first item</a>
                <a class="btn btn--ghost" href="{{ route('admin.latest-work-categories.index') }}">Manage types</a>
            </div>
        </div>
    @else
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th style="width: 46px;">Order</th>
                        <th style="width: 78px;">Image</th>
                        <th>Item</th>
                        <th style="width: 130px;">Type</th>
                        <th style="width: 110px;">Flags</th>
                        <th style="width: 190px;"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($items as $index => $item)
                        <tr>
                            <td>
                                @include('admin.partials.reorder', [
                                    'type' => 'latest-work',
                                    'id' => $item->id,
                                    'first' => $index === 0,
                                    'last' => $index === $items->count() - 1,
                                ])
                            </td>
                            <td>
                                @if ($item->coverImageUrl())
                                    <img class="thumb" src="{{ $item->coverImageUrl() }}" alt="">
                                @else
                                    <span class="thumb"></span>
                                @endif
                            </td>
                            <td>
                                <span class="table__title">{{ $item->title }}</span>
                                <p class="table__sub">
                                    #{{ $item->anchor() }}
                                    @if ($item->images_count)
                                        · {{ $item->images_count }} image{{ $item->images_count === 1 ? '' : 's' }}
                                    @endif
                                </p>
                            </td>
                            <td>
                                @if ($item->category)
                                    <span class="badge">{{ $item->category->name }}</span>
                                @else
                                    <span class="badge badge--off">—</span>
                                @endif
                            </td>
                            <td>
                                @if ($item->is_featured)
                                    <span class="badge badge--accent">featured</span>
                                @endif
                                @unless ($item->is_visible)
                                    <span class="badge badge--off">hidden</span>
                                @endunless
                            </td>
                            <td>
                                <div class="table__actions">
                                    @if ($item->is_visible)
                                        <a class="icon-btn" href="{{ route('home') }}#{{ $item->anchor() }}"
                                           target="_blank" rel="noopener" title="View on site" aria-label="View on site">
                                            @include('partials.icon', ['name' => 'arrow-up-right'])
                                        </a>
                                    @endif
                                    <a class="icon-btn" href="{{ route('admin.latest-work.edit', $item) }}"
                                       title="Edit" aria-label="Edit">
                                        @include('partials.icon', ['name' => 'edit'])
                                    </a>
                                    <form method="POST" action="{{ route('admin.latest-work.destroy', $item) }}"
                                          onsubmit="return confirm('Remove this item and its images?');">
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
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</x-layouts.admin>
