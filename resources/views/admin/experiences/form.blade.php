@php
    $editing = $experience->exists;
@endphp

<x-layouts.admin :title="$editing ? 'Edit role' : 'New role'">
    <div class="topbar">
        <div>
            <h1>{{ $editing ? 'Edit role' : 'New role' }}</h1>
            <p>Bullets and technologies are one per line.</p>
        </div>
    </div>

    <form class="form" method="POST"
          action="{{ $editing ? route('admin.experiences.update', $experience) : route('admin.experiences.store') }}">
        @csrf
        @if ($editing)
            @method('PUT')
        @endif

        <div class="card">
            <p class="card__title">Role</p>
            <p class="card__hint">Where you worked and what you did.</p>

            <div class="form__grid">
                <div class="field{{ $errors->has('position') ? ' field--invalid' : '' }}">
                    <label class="field__label" for="position">Position <span>*</span></label>
                    <input class="field__control" id="position" name="position" type="text"
                           placeholder="Team Manager" value="{{ old('position', $experience->position) }}" required>
                    @include('admin.partials.error', ['field' => 'position'])
                </div>

                <div class="field{{ $errors->has('company') ? ' field--invalid' : '' }}">
                    <label class="field__label" for="company">Company <span>*</span></label>
                    <input class="field__control" id="company" name="company" type="text"
                           placeholder="I Love Design GBR" value="{{ old('company', $experience->company) }}" required>
                    @include('admin.partials.error', ['field' => 'company'])
                </div>

                <div class="field{{ $errors->has('location') ? ' field--invalid' : '' }}">
                    <label class="field__label" for="location">Location</label>
                    <input class="field__control" id="location" name="location" type="text"
                           placeholder="Berlin, Germany" value="{{ old('location', $experience->location) }}">
                    @include('admin.partials.error', ['field' => 'location'])
                </div>

                <div class="field{{ $errors->has('company_url') ? ' field--invalid' : '' }}">
                    <label class="field__label" for="company_url">Company website</label>
                    <input class="field__control" id="company_url" name="company_url" type="text"
                           placeholder="https://…" value="{{ old('company_url', $experience->company_url) }}">
                    @include('admin.partials.error', ['field' => 'company_url'])
                </div>
            </div>
        </div>

        <div class="card">
            <p class="card__title">Dates</p>
            <p class="card__hint">
                Use the custom label for anything irregular, such as a one-month internship.
            </p>

            <div class="form__grid">
                <div class="field{{ $errors->has('start_date') ? ' field--invalid' : '' }}">
                    <label class="field__label" for="start_date">Start date</label>
                    <input class="field__control" id="start_date" name="start_date" type="date"
                           value="{{ old('start_date', $experience->start_date?->format('Y-m-d')) }}">
                    @include('admin.partials.error', ['field' => 'start_date'])
                </div>

                <div class="field{{ $errors->has('end_date') ? ' field--invalid' : '' }}">
                    <label class="field__label" for="end_date">End date</label>
                    <input class="field__control" id="end_date" name="end_date" type="date"
                           value="{{ old('end_date', $experience->end_date?->format('Y-m-d')) }}"
                           @disabled($experience->is_current)>
                    @include('admin.partials.error', ['field' => 'end_date'])
                </div>

                <div class="field{{ $errors->has('date_label') ? ' field--invalid' : '' }}">
                    <label class="field__label" for="date_label">Custom date label</label>
                    <input class="field__control" id="date_label" name="date_label" type="text"
                           placeholder="2019 · 1 month" value="{{ old('date_label', $experience->date_label) }}">
                    <p class="field__hint">Overrides the generated range when filled in.</p>
                    @include('admin.partials.error', ['field' => 'date_label'])
                </div>

                <div class="field">
                    <span class="field__label">Status</span>
                    <label class="checkbox">
                        <input type="checkbox" name="is_current" value="1" @checked(old('is_current', $experience->is_current))>
                        I currently work here
                    </label>
                </div>
            </div>
        </div>

        <div class="card">
            <p class="card__title">Details</p>
            <p class="card__hint">One responsibility or technology per line.</p>

            <div class="form__grid">
                <div class="field field--full{{ $errors->has('description') ? ' field--invalid' : '' }}">
                    <label class="field__label" for="description">Summary</label>
                    <textarea class="field__control" id="description" name="description" rows="3">{{ old('description', $experience->description) }}</textarea>
                    @include('admin.partials.error', ['field' => 'description'])
                </div>

                <div class="field field--full{{ $errors->has('responsibilities') ? ' field--invalid' : '' }}">
                    <label class="field__label" for="responsibilities">Responsibilities</label>
                    <textarea class="field__control" id="responsibilities" name="responsibilities" rows="7" placeholder="Led a team of five&#10;Ran QA on every release">{{ old('responsibilities', \App\Support\TagList::toString($experience->responsibilities ?? [])) }}</textarea>
                    <p class="field__hint">Each line becomes a bullet point on the timeline.</p>
                    @include('admin.partials.error', ['field' => 'responsibilities'])
                </div>

                <div class="field field--full{{ $errors->has('technologies') ? ' field--invalid' : '' }}">
                    <label class="field__label" for="technologies">Technologies</label>
                    <input class="field__control" id="technologies" name="technologies" type="text"
                           placeholder="Webflow, Figma, Notion"
                           value="{{ old('technologies', \App\Support\TagList::toString($experience->technologies ?? [])) }}">
                    @include('admin.partials.error', ['field' => 'technologies'])
                </div>
            </div>
        </div>

        <div class="form__actions">
            <button class="btn btn--primary" type="submit">{{ $editing ? 'Save changes' : 'Add role' }}</button>
            <a class="btn btn--ghost" href="{{ route('admin.experiences.index') }}">Cancel</a>
        </div>
    </form>
</x-layouts.admin>
