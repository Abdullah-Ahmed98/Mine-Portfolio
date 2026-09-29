<x-layouts.admin title="Skill groups">
    <div class="topbar">
        <div>
            <h1>Skill groups</h1>
            <p>The headings on the skills page. Groups with no visible skills are hidden automatically.</p>
        </div>

        <div class="topbar__actions">
            <a class="btn btn--primary btn--sm" href="{{ route('admin.skill-categories.create') }}">Add group</a>
        </div>
    </div>

    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th style="width: 46px;">Order</th>
                    <th>Name</th>
                    <th>Slug</th>
                    <th style="width: 100px;">Skills</th>
                    <th style="width: 190px;"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($categories as $index => $category)
                    <tr>
                        <td>
                            @include('admin.partials.reorder', [
                                'type' => 'skill-categories',
                                'id' => $category->id,
                                'first' => $index === 0,
                                'last' => $index === $categories->count() - 1,
                            ])
                        </td>
                        <td class="table__title">{{ $category->name }}</td>
                        <td class="table__sub">{{ $category->slug }}</td>
                        <td><span class="badge">{{ $category->skills_count }}</span></td>
                        <td>
                            <div class="table__actions">
                                <a class="icon-btn" href="{{ route('admin.skill-categories.edit', $category) }}"
                                   title="Edit" aria-label="Edit">
                                    @include('partials.icon', ['name' => 'edit'])
                                </a>
                                <form method="POST" action="{{ route('admin.skill-categories.destroy', $category) }}"
                                      onsubmit="return confirm('Remove this group and all of its skills?');">
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
                        <td colspan="5" class="empty">No skill groups yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-layouts.admin>
