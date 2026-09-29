<x-layouts.admin title="Skills">
    <div class="topbar">
        <div>
            <h1>Skills</h1>
            <p>
                Skills are listed under a group. Leave the level blank to render the skill as a plain tag,
                or set 1–100 to show a proficiency meter.
            </p>
        </div>

        <div class="topbar__actions">
            <a class="btn btn--primary btn--sm" href="{{ route('admin.skills.create') }}">Add skill</a>
        </div>
    </div>

    @forelse ($categories as $category)
        <div class="card">
            <div class="topbar" style="margin-bottom: 14px;">
                <div>
                    <p class="card__title" style="margin: 0;">{{ $category->name }}</p>
                    <p class="card__hint" style="margin: 2px 0 0;">{{ $category->skills->count() }} skills</p>
                </div>
                <div class="topbar__actions">
                    <a class="btn btn--ghost btn--sm" href="{{ route('admin.skill-categories.edit', $category) }}">
                        Rename group
                    </a>
                    <a class="btn btn--ghost btn--sm" href="{{ route('admin.skills.create') }}">Add skill</a>
                </div>
            </div>

            @if ($category->skills->isEmpty())
                <p class="empty">This group has no skills yet.</p>
            @else
                <div class="table-wrap">
                    <table class="table" style="min-width: 520px;">
                        <thead>
                            <tr>
                                <th style="width: 46px;">Order</th>
                                <th>Skill</th>
                                <th style="width: 120px;">Level</th>
                                <th style="width: 96px;">On site</th>
                                <th style="width: 130px;"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($category->skills as $index => $skill)
                                <tr>
                                    <td>
                                        @include('admin.partials.reorder', [
                                            'type' => 'skills',
                                            'id' => $skill->id,
                                            'first' => $index === 0,
                                            'last' => $index === $category->skills->count() - 1,
                                        ])
                                    </td>
                                    <td class="table__title">{{ $skill->name }}</td>
                                    <td>
                                        @if ($skill->hasLevel())
                                            <span class="badge badge--accent">{{ $skill->level }}%</span>
                                        @else
                                            <span class="badge badge--off">tag only</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($skill->is_visible)
                                            <span class="badge badge--accent">visible</span>
                                        @else
                                            <span class="badge badge--off">hidden</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="table__actions">
                                            <a class="icon-btn" href="{{ route('admin.skills.edit', $skill) }}"
                                               title="Edit" aria-label="Edit">
                                                @include('partials.icon', ['name' => 'edit'])
                                            </a>
                                            <form method="POST" action="{{ route('admin.skills.destroy', $skill) }}"
                                                  onsubmit="return confirm('Remove this skill?');">
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
        </div>
    @empty
        <p class="empty">No skill groups yet. Create one first.</p>
    @endforelse
</x-layouts.admin>
