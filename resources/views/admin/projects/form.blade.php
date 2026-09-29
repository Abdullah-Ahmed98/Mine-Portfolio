@php
    $editing = $project->exists;
@endphp

<x-layouts.admin :title="$editing ? 'Edit project' : 'New project'">
    <div class="topbar">
        <div>
            <h1>{{ $editing ? 'Edit project' : 'New project' }}</h1>
            <p>Everything here feeds the project card and the project page.</p>
        </div>
    </div>

    {{--
        The cards, the gallery and the buttons are siblings in one grid, which is
        what the single form used to provide.

        The buttons live outside the form and name it with form="project-form".
        HTML does not allow one form inside another: a parser closes the outer
        form at the first inner <form> it meets, which would strand everything
        after it - the save button included - outside any form, so clicking save
        would quietly submit nothing. The gallery needs its own form because it
        posts to a different endpoint, so the two are kept side by side instead.
    --}}
    <div class="form">
        <form class="form__group" id="project-form" method="POST"
              action="{{ $editing ? route('admin.projects.update', $project) : route('admin.projects.store') }}"
              enctype="multipart/form-data">
            @csrf
            @if ($editing)
                @method('PUT')
            @endif

            <div class="card">
                <p class="card__title">Basics</p>
                <p class="card__hint">The title, category and summary appear on the card.</p>

                <div class="form__grid">
                    <div class="field{{ $errors->has('title') ? ' field--invalid' : '' }}">
                        <label class="field__label" for="title">Title <span>*</span></label>
                        <input class="field__control" id="title" name="title" type="text"
                               value="{{ old('title', $project->title) }}" required>
                        @include('admin.partials.error', ['field' => 'title'])
                    </div>

                    <div class="field{{ $errors->has('project_category_id') ? ' field--invalid' : '' }}">
                        <label class="field__label" for="project_category_id">Section</label>
                        <select class="field__control" id="project_category_id" name="project_category_id">
                            <option value="">Not filed yet</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}"
                                    @selected((int) old('project_category_id', $project->project_category_id) === $category->id)>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                        <p class="field__hint">Each category is a section on the site. Required once published.</p>
                        @include('admin.partials.error', ['field' => 'project_category_id'])
                    </div>

                    <div class="field field--full{{ $errors->has('slug') ? ' field--invalid' : '' }}">
                        <label class="field__label" for="slug">URL slug</label>
                        <input class="field__control" id="slug" name="slug" type="text"
                               placeholder="traffic-sign-analytics"
                               value="{{ old('slug', $project->slug) }}">
                        <p class="field__hint">Generated from the title when left blank, and always made unique.</p>
                        @include('admin.partials.error', ['field' => 'slug'])
                    </div>

                    <div class="field field--full{{ $errors->has('short_description') ? ' field--invalid' : '' }}">
                        <label class="field__label" for="short_description">Short description</label>
                        <textarea class="field__control" id="short_description" name="short_description" rows="3">{{ old('short_description', $project->short_description) }}</textarea>
                        <p class="field__hint">One or two sentences for the card and the meta description.</p>
                        @include('admin.partials.error', ['field' => 'short_description'])
                    </div>

                    <div class="field field--full{{ $errors->has('full_description') ? ' field--invalid' : '' }}">
                        <label class="field__label" for="full_description">Full description</label>
                        <textarea class="field__control" id="full_description" name="full_description" rows="8">{{ old('full_description', $project->full_description) }}</textarea>
                        <p class="field__hint">
                            Optional. Leave blank to reuse the short description. Separate paragraphs with a blank line.
                        </p>
                        @include('admin.partials.error', ['field' => 'full_description'])
                    </div>
                </div>
            </div>

            <div class="card">
                <p class="card__title">Cover image</p>
                <p class="card__hint">Shown on the card and at the top of the project page.</p>

                <div class="split">
                    <div class="field{{ $errors->has('featured_image') ? ' field--invalid' : '' }}">
                        <label class="field__label" for="featured_image">Upload a new image</label>
                        <input class="field__control" id="featured_image" name="featured_image" type="file"
                               accept="image/jpeg,image/png,image/webp">
                        <p class="field__hint">JPG, PNG or WebP up to 8 MB. Replaces the current cover.</p>
                        @include('admin.partials.error', ['field' => 'featured_image'])
                    </div>

                    <div>
                        @if ($project->coverImageUrl())
                            <img class="file-preview" src="{{ $project->coverImageUrl() }}" alt="Current cover">
                            <label class="checkbox" style="margin-top: 10px;">
                                <input type="checkbox" name="remove_image" value="1">
                                Remove cover
                            </label>
                        @else
                            <p class="field__hint">No cover yet — the card falls back to a generated pattern.</p>
                        @endif
                    </div>
                </div>
            </div>

            <div class="card">
                <p class="card__title">Details</p>

                <div class="form__grid">
                    <div class="field{{ $errors->has('client_name') ? ' field--invalid' : '' }}">
                        <label class="field__label" for="client_name">Client / organisation</label>
                        <input class="field__control" id="client_name" name="client_name" type="text"
                               value="{{ old('client_name', $project->client_name) }}">
                        @include('admin.partials.error', ['field' => 'client_name'])
                    </div>

                    <div class="field{{ $errors->has('client_role') ? ' field--invalid' : '' }}">
                        <label class="field__label" for="client_role">Client role / context</label>
                        <input class="field__control" id="client_role" name="client_role" type="text"
                               placeholder="Final Year Project"
                               value="{{ old('client_role', $project->client_role) }}">
                        @include('admin.partials.error', ['field' => 'client_role'])
                    </div>

                    <div class="field{{ $errors->has('completed_at') ? ' field--invalid' : '' }}">
                        <label class="field__label" for="completed_at">Completed</label>
                        <input class="field__control" id="completed_at" name="completed_at" type="date"
                               value="{{ old('completed_at', $project->completed_at?->format('Y-m-d')) }}">
                        @include('admin.partials.error', ['field' => 'completed_at'])
                    </div>

                    <div class="field{{ $errors->has('technologies') ? ' field--invalid' : '' }}">
                        <label class="field__label" for="technologies">Technologies</label>
                        <input class="field__control" id="technologies" name="technologies" type="text"
                               placeholder="Python, YOLO, Deep Learning"
                               value="{{ old('technologies', \App\Support\TagList::toString($project->technologies ?? [])) }}">
                        <p class="field__hint">Comma separated.</p>
                        @include('admin.partials.error', ['field' => 'technologies'])
                    </div>

                    <div class="field{{ $errors->has('project_url') ? ' field--invalid' : '' }}">
                        <label class="field__label" for="project_url">Live project URL</label>
                        <input class="field__control" id="project_url" name="project_url" type="text"
                               placeholder="https://…" value="{{ old('project_url', $project->project_url) }}">
                        @include('admin.partials.error', ['field' => 'project_url'])
                    </div>

                    <div class="field{{ $errors->has('github_url') ? ' field--invalid' : '' }}">
                        <label class="field__label" for="github_url">GitHub URL</label>
                        <input class="field__control" id="github_url" name="github_url" type="text"
                               placeholder="https://github.com/…"
                               value="{{ old('github_url', $project->github_url) }}">
                        @include('admin.partials.error', ['field' => 'github_url'])
                    </div>

                    <div class="field">
                        <span class="field__label">Visibility</span>
                        <label class="checkbox">
                            <input type="checkbox" name="is_published" value="1"
                                   @checked(old('is_published', $project->exists ? $project->is_published : true))>
                            Published
                        </label>
                        <label class="checkbox">
                            <input type="checkbox" name="is_featured" value="1"
                                   @checked(old('is_featured', $project->is_featured))>
                            Feature on the home page
                        </label>
                    </div>
                </div>
            </div>
        </form>

        @if ($editing)
            <div class="card">
                <p class="card__title">Gallery</p>
                <p class="card__hint">Up to 10 images at a time. The first one is used if no cover is set.</p>

                @if ($project->images->isEmpty())
                    <p class="empty">No gallery images yet.</p>
                @else
                    <div class="image-grid">
                        @foreach ($project->images as $index => $image)
                            <div class="image-item">
                                <img class="image-item__preview" src="{{ $image->url() }}"
                                     alt="{{ $image->alt ?: $project->title }}">

                                <form method="POST" action="{{ route('admin.projects.images.update', $image) }}"
                                      class="image-item__row">
                                    @csrf
                                    @method('PUT')
                                    <div class="field">
                                        <label class="field__label" for="alt-{{ $image->id }}">Alt text</label>
                                        <input class="field__control" id="alt-{{ $image->id }}" name="alt" type="text"
                                               value="{{ $image->alt }}" placeholder="Describe the image">
                                    </div>
                                    <label class="checkbox" title="Use as the cover">
                                        <input type="checkbox" name="is_cover" value="1" @checked($image->is_cover)>
                                        Cover
                                    </label>
                                    <button class="btn btn--ghost btn--sm" type="submit">Save</button>
                                </form>

                                <div class="image-item__row">
                                    @include('admin.partials.reorder', [
                                        'type' => 'project-images',
                                        'id' => $image->id,
                                        'first' => $index === 0,
                                        'last' => $index === $project->images->count() - 1,
                                    ])

                                    <form method="POST" action="{{ route('admin.projects.images.destroy', $image) }}"
                                          onsubmit="return confirm('Delete this image?');" style="margin-left: auto;">
                                        @csrf
                                        @method('DELETE')
                                        <button class="icon-btn icon-btn--danger" type="submit"
                                                title="Delete image" aria-label="Delete image">
                                            @include('partials.icon', ['name' => 'trash'])
                                        </button>
                                    </form>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <form class="form__group form__divider" method="POST"
                  action="{{ route('admin.projects.images.store', $project) }}"
                  enctype="multipart/form-data">
                @csrf

                <p class="group-block__title">Add images</p>

                <div class="form__grid">
                    <div class="field">
                        <label class="field__label" for="images">Choose files</label>
                        <input class="field__control" id="images" name="images[]" type="file" multiple
                               accept="image/jpeg,image/png,image/webp,image/gif">
                        <p class="field__hint">JPG, PNG, WebP or GIF up to 8 MB each.</p>
                    </div>

                    <div class="field">
                        <label class="field__label" for="alt">Alt text</label>
                        <input class="field__control" id="alt" name="alt" type="text"
                               placeholder="Applies to every file in this upload" value="{{ old('alt') }}">
                    </div>
                </div>

                <div class="form__actions">
                    <button class="btn btn--ghost" type="submit">Upload</button>
                </div>
            </form>
        @endif

        <div class="form__actions">
            <button class="btn btn--primary" type="submit" form="project-form">{{ $editing ? 'Save project' : 'Create project' }}</button>
            <a class="btn btn--ghost" href="{{ route('admin.projects.index') }}">Cancel</a>
        </div>
    </div>
</x-layouts.admin>
