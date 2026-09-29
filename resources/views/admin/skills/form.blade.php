@php
    $editing = $skill->exists;
@endphp

<x-layouts.admin :title="$editing ? 'Edit skill' : 'New skill'">
    <div class="topbar">
        <div>
            <h1>{{ $editing ? 'Edit skill' : 'New skill' }}</h1>
            <p>Give it a group, and optionally a proficiency level.</p>
        </div>
    </div>

    <form class="form" method="POST"
          action="{{ $editing ? route('admin.skills.update', $skill) : route('admin.skills.store') }}">
        @csrf
        @if ($editing)
            @method('PUT')
        @endif

        <div class="card">
            <div class="form__grid">
                <div class="field{{ $errors->has('skill_category_id') ? ' field--invalid' : '' }}">
                    <label class="field__label" for="skill_category_id">Group <span>*</span></label>
                    <select class="field__control" id="skill_category_id" name="skill_category_id" required>
                        @foreach ($categories as $option)
                            <option value="{{ $option->id }}"
                                @selected((int) old('skill_category_id', $skill->skill_category_id) === $option->id)>
                                {{ $option->name }}
                            </option>
                        @endforeach
                    </select>
                    @include('admin.partials.error', ['field' => 'skill_category_id'])
                </div>

                <div class="field{{ $errors->has('name') ? ' field--invalid' : '' }}">
                    <label class="field__label" for="name">Skill <span>*</span></label>
                    <input class="field__control" id="name" name="name" type="text"
                           placeholder="Webflow" value="{{ old('name', $skill->name) }}" required>
                    @include('admin.partials.error', ['field' => 'name'])
                </div>

                <div class="field{{ $errors->has('level') ? ' field--invalid' : '' }}">
                    <label class="field__label" for="level">Level (%)</label>
                    <input class="field__control" id="level" name="level" type="number" min="1" max="100"
                           placeholder="Optional" value="{{ old('level', $skill->level) }}">
                    <p class="field__hint">Leave blank to show it as a plain tag.</p>
                    @include('admin.partials.error', ['field' => 'level'])
                </div>

                <div class="field">
                    <span class="field__label">Visibility</span>
                    <label class="checkbox">
                        <input type="checkbox" name="is_visible" value="1"
                               @checked(old('is_visible', $skill->exists ? $skill->is_visible : true))>
                        Show on the site
                    </label>
                </div>
            </div>
        </div>

        <div class="form__actions">
            <button class="btn btn--primary" type="submit">{{ $editing ? 'Save changes' : 'Add skill' }}</button>
            <a class="btn btn--ghost" href="{{ route('admin.skills.index') }}">Cancel</a>
        </div>
    </form>
</x-layouts.admin>
