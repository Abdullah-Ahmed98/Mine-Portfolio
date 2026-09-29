<x-layouts.admin title="Projects">
    <div class="topbar">
        <div>
            <h1>Projects</h1>
            <p>Featured projects also appear in the “Selected work” grid on the home page.</p>
        </div>

        <div class="topbar__actions">
            <a class="btn btn--primary btn--sm" href="{{ route('admin.projects.create') }}">Add project</a>
        </div>
    </div>

    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th style="width: 46px;">Order</th>
                    <th style="width: 78px;">Cover</th>
                    <th>Project</th>
                    <th style="width: 130px;">Category</th>
                    <th style="width: 110px;">Flags</th>
                    <th style="width: 190px;"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($projects as $index => $project)
                    <tr>
                        <td>
                            @include('admin.partials.reorder', [
                                'type' => 'projects',
                                'id' => $project->id,
                                'first' => $index === 0,
                                'last' => $index === $projects->count() - 1,
                            ])
                        </td>
                        <td>
                            @if ($project->coverImageUrl())
                                <img class="thumb" src="{{ $project->coverImageUrl() }}" alt="">
                            @else
                                <span class="thumb"></span>
                            @endif
                        </td>
                        <td>
                            <span class="table__title">{{ $project->title }}</span>
                            <p class="table__sub">
                                /projects/{{ $project->slug }}
                                @if ($project->images_count)
                                    · {{ $project->images_count }} image{{ $project->images_count === 1 ? '' : 's' }}
                                @endif
                            </p>
                        </td>
                        <td>
                            @if ($project->category)
                                <span class="badge">{{ $project->category->name }}</span>
                            @else
                                <span class="badge badge--off">—</span>
                            @endif
                        </td>
                        <td>
                            @if ($project->is_featured)
                                <span class="badge badge--accent">featured</span>
                            @endif
                            @if (! $project->is_published)
                                <span class="badge badge--off">draft</span>
                            @endif
                        </td>
                        <td>
                            <div class="table__actions">
                                @if ($project->is_published && $project->category)
                                    <a class="icon-btn" href="{{ route('home') }}#{{ $project->anchor() }}"
                                       target="_blank" rel="noopener" title="View on site" aria-label="View on site">
                                        @include('partials.icon', ['name' => 'arrow-up-right'])
                                    </a>
                                @endif
                                <a class="icon-btn" href="{{ route('admin.projects.edit', $project) }}"
                                   title="Edit" aria-label="Edit">
                                    @include('partials.icon', ['name' => 'edit'])
                                </a>
                                <form method="POST" action="{{ route('admin.projects.destroy', $project) }}"
                                      onsubmit="return confirm('Remove this project and its images?');">
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
                        <td colspan="6" class="empty">No projects yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-layouts.admin>
