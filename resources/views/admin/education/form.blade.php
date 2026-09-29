@php
    $editing = $entry->exists;
@endphp

<x-layouts.admin :title="$editing ? 'Edit entry' : 'New entry'">
    <div class="topbar">
        <div>
            <h1>{{ $editing ? 'Edit entry' : 'New entry' }}</h1>
            <p>A degree, a certification, or a competition result.</p>
        </div>
    </div>

    <form class="form" method="POST"
          action="{{ $editing ? route('admin.education.update', $entry) : route('admin.education.store') }}">
        @csrf
        @if ($editing)
            @method('PUT')
        @endif

        <div class="card">
            <div class="form__grid">
                <div class="field{{ $errors->has('title') ? ' field--invalid' : '' }}">
                    <label class="field__label" for="title">Title <span>*</span></label>
                    <input class="field__control" id="title" name="title" type="text"
                           placeholder="B.S. in Computer Science" value="{{ old('title', $entry->title) }}" required>
                    @include('admin.partials.error', ['field' => 'title'])
                </div>

                <div class="field{{ $errors->has('institution') ? ' field--invalid' : '' }}">
                    <label class="field__label" for="institution">Institution</label>
                    <input class="field__control" id="institution" name="institution" type="text"
                           placeholder="University of Wah" value="{{ old('institution', $entry->institution) }}">
                    @include('admin.partials.error', ['field' => 'institution'])
                </div>

                <div class="field{{ $errors->has('meta') ? ' field--invalid' : '' }}">
                    <label class="field__label" for="meta">Meta line</label>
                    <input class="field__control" id="meta" name="meta" type="text"
                           placeholder="1st Position — Speed Programming &amp; Web Designing"
                           value="{{ old('meta', $entry->meta) }}">
                    <p class="field__hint">Shown under the title when there is no institution.</p>
                    @include('admin.partials.error', ['field' => 'meta'])
                </div>

                <div class="field{{ $errors->has('start_date') ? ' field--invalid' : '' }}">
                    <label class="field__label" for="start_date">Start date</label>
                    <input class="field__control" id="start_date" name="start_date" type="date"
                           value="{{ old('start_date', $entry->start_date?->format('Y-m-d')) }}">
                    @include('admin.partials.error', ['field' => 'start_date'])
                </div>

                <div class="field{{ $errors->has('end_date') ? ' field--invalid' : '' }}">
                    <label class="field__label" for="end_date">End date</label>
                    <input class="field__control" id="end_date" name="end_date" type="date"
                           value="{{ old('end_date', $entry->end_date?->format('Y-m-d')) }}">
                    @include('admin.partials.error', ['field' => 'end_date'])
                </div>

                <div class="field field--full{{ $errors->has('description') ? ' field--invalid' : '' }}">
                    <label class="field__label" for="description">Description</label>
                    <textarea class="field__control" id="description" name="description" rows="3">{{ old('description', $entry->description) }}</textarea>
                    @include('admin.partials.error', ['field' => 'description'])
                </div>
            </div>
        </div>

        <div class="form__actions">
            <button class="btn btn--primary" type="submit">{{ $editing ? 'Save changes' : 'Add entry' }}</button>
            <a class="btn btn--ghost" href="{{ route('admin.education.index') }}">Cancel</a>
        </div>
    </form>
</x-layouts.admin>
