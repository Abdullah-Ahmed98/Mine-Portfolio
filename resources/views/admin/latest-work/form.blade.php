@php
    $editing = $item->exists;
@endphp

<x-layouts.admin :title="$editing ? 'Edit latest work' : 'New latest work'">
    <div class="topbar">
        <div>
            <h1>{{ $editing ? 'Edit latest work' : 'New latest work' }}</h1>
            <p>Everything here feeds one card in the “Latest work” section of the home page.</p>
        </div>
    </div>

    {{--
        The cards, the extra images and the buttons are siblings in one grid,
        which is what the single form used to provide.

        The buttons live outside the form and name it with form="latest-work-form".
        HTML does not allow one form inside another: a parser closes the outer
        form at the first inner <form> it meets, which would strand everything
        after it - the save button included - outside any form, so clicking save
        would quietly submit nothing. The image upload needs its own form because
        it posts to a different endpoint, so the two are kept side by side.
    --}}
    <div class="form">
        <form class="form__group" id="latest-work-form" method="POST"
              action="{{ $editing ? route('admin.latest-work.update', $item) : route('admin.latest-work.store') }}"
              enctype="multipart/form-data">
            @csrf
            @if ($editing)
                @method('PUT')
            @endif

            <div class="card">
                <p class="card__title">Basics</p>
                <p class="card__hint">The title, type and summary appear on the card.</p>

                <div class="form__grid">
                    <div class="field{{ $errors->has('title') ? ' field--invalid' : '' }}">
                        <label class="field__label" for="title">Title <span>*</span></label>
                        <input class="field__control" id="title" name="title" type="text"
                               value="{{ old('title', $item->title) }}" required>
                        @include('admin.partials.error', ['field' => 'title'])
                    </div>

                    <div class="field{{ $errors->has('latest_work_category_id') ? ' field--invalid' : '' }}">
                        <label class="field__label" for="latest_work_category_id">Type / category</label>
                        <select class="field__control" id="latest_work_category_id" name="latest_work_category_id">
                            <option value="">No type</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}"
                                    @selected((int) old('latest_work_category_id', $item->latest_work_category_id) === $category->id)>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                        <p class="field__hint">Shown under the title. Manage these under “Types”.</p>
                        @include('admin.partials.error', ['field' => 'latest_work_category_id'])
                    </div>

                    <div class="field field--full{{ $errors->has('slug') ? ' field--invalid' : '' }}">
                        <label class="field__label" for="slug">URL slug</label>
                        <input class="field__control" id="slug" name="slug" type="text"
                               placeholder="traffic-sign-analytics"
                               value="{{ old('slug', $item->slug) }}">
                        <p class="field__hint">Generated from the title when left blank, and always made unique.</p>
                        @include('admin.partials.error', ['field' => 'slug'])
                    </div>

                    <div class="field field--full{{ $errors->has('short_description') ? ' field--invalid' : '' }}">
                        <label class="field__label" for="short_description">Short description</label>
                        <textarea class="field__control" id="short_description" name="short_description" rows="3">{{ old('short_description', $item->short_description) }}</textarea>
                        <p class="field__hint">One or two sentences, shown under the title on the card.</p>
                        @include('admin.partials.error', ['field' => 'short_description'])
                    </div>

                    <div class="field field--full{{ $errors->has('details') ? ' field--invalid' : '' }}">
                        <label class="field__label" for="details">Extra details</label>
                        <textarea class="field__control" id="details" name="details" rows="5">{{ old('details', $item->details) }}</textarea>
                        <p class="field__hint">
                            Optional. Leave blank to reuse the short description. Separate paragraphs with a blank line.
                        </p>
                        @include('admin.partials.error', ['field' => 'details'])
                    </div>
                </div>
            </div>

            <div class="card">
                <p class="card__title">Image</p>
                <p class="card__hint">The card's main image. Without one the card falls back to a generated pattern.</p>

                <div class="split">
                    <div class="field{{ $errors->has('image') ? ' field--invalid' : '' }}">
                        <label class="field__label" for="image">Upload an image</label>
                        <input class="field__control" id="image" name="image" type="file"
                               accept="image/jpeg,image/png,image/webp">
                        <p class="field__hint">JPG, PNG or WebP up to 8 MB. Replaces the current image.</p>
                        @include('admin.partials.error', ['field' => 'image'])
                    </div>

                    <div>
                        @if ($item->coverImageUrl())
                            <img class="file-preview" src="{{ $item->coverImageUrl() }}" alt="Current image">
                            <label class="checkbox" style="margin-top: 10px;">
                                <input type="checkbox" name="remove_image" value="1">
                                Remove image
                            </label>
                        @else
                            <p class="field__hint">No image yet.</p>
                        @endif
                    </div>
                </div>
            </div>

            <div class="card">
                <p class="card__title">Details</p>

                <div class="form__grid">
                    <div class="field{{ $errors->has('technologies') ? ' field--invalid' : '' }}">
                        <label class="field__label" for="technologies">Technologies</label>
                        <input class="field__control" id="technologies" name="technologies" type="text"
                               placeholder="Webflow, Figma, GSAP"
                               value="{{ old('technologies', \App\Support\TagList::toString($item->technologies ?? [])) }}">
                        <p class="field__hint">Comma separated.</p>
                        @include('admin.partials.error', ['field' => 'technologies'])
                    </div>

                    <div class="field{{ $errors->has('project_url') ? ' field--invalid' : '' }}">
                        <label class="field__label" for="project_url">Project URL</label>
                        <input class="field__control" id="project_url" name="project_url" type="text"
                               placeholder="https://…" value="{{ old('project_url', $item->project_url) }}">
                        <p class="field__hint">Makes the whole card a link.</p>
                        @include('admin.partials.error', ['field' => 'project_url'])
                    </div>

                    <div class="field">
                        <span class="field__label">Visibility</span>
                        <label class="checkbox">
                            <input type="checkbox" name="is_visible" value="1"
                                   @checked(old('is_visible', $item->exists ? $item->is_visible : true))>
                            Visible on the site
                        </label>
                        <label class="checkbox">
                            <input type="checkbox" name="is_featured" value="1"
                                   @checked(old('is_featured', $item->is_featured))>
                            Mark as featured
                        </label>
                        <p class="field__hint">Unticking “Visible” hides the card without deleting it.</p>
                    </div>
                </div>
            </div>
        </form>

        @if ($editing)
            <div class="card">
                <p class="card__title">Extra images</p>
                <p class="card__hint">Up to 10 at a time. These appear under the card's main image.</p>

                @if ($item->images->isEmpty())
                    <p class="empty">No extra images yet.</p>
                @else
                    <div class="image-grid">
                        @foreach ($item->images as $index => $image)
                            <div class="image-item">
                                <img class="image-item__preview" src="{{ $image->url() }}"
                                     alt="{{ $image->alt ?: $item->title }}">

                                <form method="POST" action="{{ route('admin.latest-work.images.update', $image) }}"
                                      class="image-item__row">
                                    @csrf
                                    @method('PUT')
                                    <div class="field">
                                        <label class="field__label" for="alt-{{ $image->id }}">Alt text</label>
                                        <input class="field__control" id="alt-{{ $image->id }}" name="alt" type="text"
                                               value="{{ $image->alt }}" placeholder="Describe the image">
                                    </div>
                                    <button class="btn btn--ghost btn--sm" type="submit">Save</button>
                                </form>

                                <div class="image-item__row">
                                    @include('admin.partials.reorder', [
                                        'type' => 'latest-work-images',
                                        'id' => $image->id,
                                        'first' => $index === 0,
                                        'last' => $index === $item->images->count() - 1,
                                    ])

                                    <form method="POST" action="{{ route('admin.latest-work.images.destroy', $image) }}"
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
                  action="{{ route('admin.latest-work.images.store', $item) }}"
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
            <button class="btn btn--primary" type="submit" form="latest-work-form">{{ $editing ? 'Save item' : 'Create item' }}</button>
            <a class="btn btn--ghost" href="{{ route('admin.latest-work.index') }}">Cancel</a>
        </div>
    </div>
</x-layouts.admin>
