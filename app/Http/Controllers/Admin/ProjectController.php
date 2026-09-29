<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProjectRequest;
use App\Models\Project;
use App\Models\ProjectCategory;
use App\Services\MediaService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;

class ProjectController extends Controller
{
    public function index(): View
    {
        return view('admin.projects.index', [
            'projects' => Project::query()
                ->with('category')
                ->withCount('images')
                ->ordered()
                ->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.projects.form', [
            'project' => new Project,
            'categories' => $this->categoryOptions(),
        ]);
    }

    public function store(ProjectRequest $request, MediaService $media): RedirectResponse
    {
        $validated = $request->validated();

        if ($request->hasFile('featured_image')) {
            $validated['featured_image'] = $media->store(
                $request->file('featured_image'),
                'portfolio/projects',
            );
        }

        $validated['slug'] = $this->resolveSlug($request, $validated['title'] ?? null);

        Project::create([...$validated, 'sort_order' => Project::nextSortOrder()]);

        return redirect()->route('admin.projects.index')->with('status', 'Project added.');
    }

    public function edit(Project $project): View
    {
        $project->load('images');

        return view('admin.projects.form', [
            'project' => $project,
            'categories' => $this->categoryOptions(),
        ]);
    }

    public function update(ProjectRequest $request, Project $project, MediaService $media): RedirectResponse
    {
        $validated = $request->validated();

        if ($request->boolean('remove_image')) {
            $media->delete($project->featured_image);
            $validated['featured_image'] = null;
        } elseif ($request->hasFile('featured_image')) {
            $validated['featured_image'] = $media->replace(
                $project->featured_image,
                $request->file('featured_image'),
                'portfolio/projects',
            );
        }

        $validated['slug'] = $this->resolveSlug($request, $validated['title'] ?? null, $project);

        $project->update($validated);

        return redirect()->route('admin.projects.index')->with('status', 'Project updated.');
    }

    public function destroy(Project $project, MediaService $media): RedirectResponse
    {
        $media->delete($project->featured_image);

        foreach ($project->images as $image) {
            $media->delete($image->path);
        }

        $project->delete();

        return back()->with('status', 'Project removed.');
    }

    /**
     * A blank slug field means "derive it from the title", which is what the
     * form's hint promises. An update that omits the field entirely keeps the
     * current slug, so editing a title can never silently break a URL that has
     * already been shared or indexed.
     */
    private function resolveSlug(ProjectRequest $request, ?string $title, ?Project $current = null): string
    {
        if ($current !== null && ! $request->has('slug')) {
            return $current->slug;
        }

        $requested = $request->input('slug');

        return $this->uniqueSlug($title, $requested, $current);
    }

    private function uniqueSlug(?string $title, ?string $requested, ?Project $current = null): string
    {
        $base = Project::slugFor(filled($requested) ? $requested : ($title ?? 'project'));

        $slug = $base;
        $suffix = 2;

        while (
            Project::query()
                ->where('slug', $slug)
                ->when($current, fn ($query) => $query->whereKeyNot($current->getKey()))
                ->exists()
        ) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }

    /**
     * @return Collection<int, ProjectCategory>
     */
    private function categoryOptions()
    {
        return ProjectCategory::query()->orderBy('sort_order')->get();
    }
}
