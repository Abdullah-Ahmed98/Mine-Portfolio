@php
    $editing = $category->exists;
@endphp

<x-layouts.admin :title="$editing ? 'Edit category' : 'New category'">
    <div class="topbar">
        <div>
            <h1>{{ $editing ? 'Edit project category' : 'New project category' }}</h1>
            <p>Each category becomes its own showcase section on the single-page site, in the order below.</p>
        </div>
    </div>

    <form class="form" method="POST"
          action="{{ $editing ? route('admin.project-categories.update', $category) : route('admin.project-categories.store') }}">
        @csrf
        @if ($editing)
            @method('PUT')
        @endif

        <div class="card">
            <div class="form__grid">
                <div class="field{{ $errors->has('name') ? ' field--invalid' : '' }}">
                    <label class="field__label" for="name">Name <span>*</span></label>
                    <input class="field__control" id="name" name="name" type="text"
                           placeholder="Academic" value="{{ old('name', $category->name) }}" required>
                    @include('admin.partials.error', ['field' => 'name'])
                </div>

                <div class="field{{ $errors->has('slug') ? ' field--invalid' : '' }}">
                    <label class="field__label" for="slug">Slug</label>
                    <input class="field__control" id="slug" name="slug" type="text"
                           placeholder="academic" value="{{ old('slug', $category->slug) }}">
                    <p class="field__hint">Generated from the name when left blank.</p>
                    @include('admin.partials.error', ['field' => 'slug'])
                </div>

                <div class="field field--full{{ $errors->has('description') ? ' field--invalid' : '' }}">
                    <label class="field__label" for="description">Section intro</label>
                    <textarea class="field__control" id="description" name="description" rows="3"
                              placeholder="One or two sentences describing this kind of work."
                              maxlength="400">{{ old('description', $category->description) }}</textarea>
                    <p class="field__hint">Shown under the section heading on the site.</p>
                    @include('admin.partials.error', ['field' => 'description'])
                </div>
            </div>
        </div>

        <div class="form__actions">
            <button class="btn btn--primary" type="submit">{{ $editing ? 'Save changes' : 'Add category' }}</button>
            <a class="btn btn--ghost" href="{{ route('admin.project-categories.index') }}">Cancel</a>
        </div>
    </form>
</x-layouts.admin>
