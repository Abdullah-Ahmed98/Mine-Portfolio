@php
    $editing = $category->exists;
@endphp

<x-layouts.admin :title="$editing ? 'Edit skill group' : 'New skill group'">
    <div class="topbar">
        <div>
            <h1>{{ $editing ? 'Edit skill group' : 'New skill group' }}</h1>
            <p>The heading a group of skills is listed under.</p>
        </div>
    </div>

    <form class="form" method="POST"
          action="{{ $editing ? route('admin.skill-categories.update', $category) : route('admin.skill-categories.store') }}">
        @csrf
        @if ($editing)
            @method('PUT')
        @endif

        <div class="card">
            <div class="form__grid">
                <div class="field{{ $errors->has('name') ? ' field--invalid' : '' }}">
                    <label class="field__label" for="name">Name <span>*</span></label>
                    <input class="field__control" id="name" name="name" type="text"
                           placeholder="Development" value="{{ old('name', $category->name) }}" required>
                    @include('admin.partials.error', ['field' => 'name'])
                </div>

                <div class="field{{ $errors->has('slug') ? ' field--invalid' : '' }}">
                    <label class="field__label" for="slug">Slug</label>
                    <input class="field__control" id="slug" name="slug" type="text"
                           placeholder="development" value="{{ old('slug', $category->slug) }}">
                    <p class="field__hint">Generated from the name when left blank.</p>
                    @include('admin.partials.error', ['field' => 'slug'])
                </div>
            </div>
        </div>

        <div class="form__actions">
            <button class="btn btn--primary" type="submit">{{ $editing ? 'Save changes' : 'Add group' }}</button>
            <a class="btn btn--ghost" href="{{ route('admin.skill-categories.index') }}">Cancel</a>
        </div>
    </form>
</x-layouts.admin>
