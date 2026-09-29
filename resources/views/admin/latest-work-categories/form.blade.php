@php
    $editing = $category->exists;
@endphp

<x-layouts.admin :title="$editing ? 'Edit type' : 'New type'">
    <div class="topbar">
        <div>
            <h1>{{ $editing ? 'Edit latest work type' : 'New latest work type' }}</h1>
            <p>Types label the latest-work cards. They do not create sections of their own.</p>
        </div>
    </div>

    <form class="form" method="POST"
          action="{{ $editing ? route('admin.latest-work-categories.update', $category) : route('admin.latest-work-categories.store') }}">
        @csrf
        @if ($editing)
            @method('PUT')
        @endif

        <div class="card">
            <div class="form__grid">
                <div class="field{{ $errors->has('name') ? ' field--invalid' : '' }}">
                    <label class="field__label" for="name">Name <span>*</span></label>
                    <input class="field__control" id="name" name="name" type="text"
                           placeholder="UI/UX Design" value="{{ old('name', $category->name) }}" required>
                    @include('admin.partials.error', ['field' => 'name'])
                </div>

                <div class="field{{ $errors->has('slug') ? ' field--invalid' : '' }}">
                    <label class="field__label" for="slug">Slug</label>
                    <input class="field__control" id="slug" name="slug" type="text"
                           placeholder="ui-ux-design" value="{{ old('slug', $category->slug) }}">
                    <p class="field__hint">Generated from the name when left blank.</p>
                    @include('admin.partials.error', ['field' => 'slug'])
                </div>

                <div class="field field--full{{ $errors->has('description') ? ' field--invalid' : '' }}">
                    <label class="field__label" for="description">Description</label>
                    <textarea class="field__control" id="description" name="description" rows="3"
                              placeholder="One or two sentences about this kind of work."
                              maxlength="400">{{ old('description', $category->description) }}</textarea>
                    <p class="field__hint">Kept for reference in the admin. The card only shows the name.</p>
                    @include('admin.partials.error', ['field' => 'description'])
                </div>
            </div>
        </div>

        <div class="form__actions">
            <button class="btn btn--primary" type="submit">{{ $editing ? 'Save changes' : 'Add type' }}</button>
            <a class="btn btn--ghost" href="{{ route('admin.latest-work-categories.index') }}">Cancel</a>
        </div>
    </form>
</x-layouts.admin>
