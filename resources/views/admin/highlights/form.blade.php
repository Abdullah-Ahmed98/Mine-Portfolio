@php
    $editing = $highlight->exists;
@endphp

<x-layouts.admin :title="$editing ? 'Edit highlight' : 'New highlight'">
    <div class="topbar">
        <div>
            <h1>{{ $editing ? 'Edit highlight' : 'New highlight' }}</h1>
            <p>A short label and one or two sentences.</p>
        </div>
    </div>

    <form class="form" method="POST"
          action="{{ $editing ? route('admin.highlights.update', $highlight) : route('admin.highlights.store') }}">
        @csrf
        @if ($editing)
            @method('PUT')
        @endif

        <div class="card">
            <div class="form__grid">
                <div class="field{{ $errors->has('title') ? ' field--invalid' : '' }}">
                    <label class="field__label" for="title">Title <span>*</span></label>
                    <input class="field__control" id="title" name="title" type="text"
                           placeholder="Key strengths" value="{{ old('title', $highlight->title) }}" required>
                    @include('admin.partials.error', ['field' => 'title'])
                </div>

                <div class="field field--full{{ $errors->has('text') ? ' field--invalid' : '' }}">
                    <label class="field__label" for="text">Text <span>*</span></label>
                    <textarea class="field__control" id="text" name="text" rows="3" required>{{ old('text', $highlight->text) }}</textarea>
                    @include('admin.partials.error', ['field' => 'text'])
                </div>
            </div>
        </div>

        <div class="form__actions">
            <button class="btn btn--primary" type="submit">{{ $editing ? 'Save changes' : 'Add highlight' }}</button>
            <a class="btn btn--ghost" href="{{ route('admin.highlights.index') }}">Cancel</a>
        </div>
    </form>
</x-layouts.admin>
