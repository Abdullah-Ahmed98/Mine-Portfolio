@php
    $editing = $socialLink->exists;
@endphp

<x-layouts.admin :title="$editing ? 'Edit link' : 'Add link'">
    <div class="topbar">
        <div>
            <h1>{{ $editing ? 'Edit social link' : 'New social link' }}</h1>
            <p>Leave the URL blank to keep the platform off the site.</p>
        </div>
    </div>

    <form class="form" method="POST"
          action="{{ $editing ? route('admin.social-links.update', $socialLink) : route('admin.social-links.store') }}">
        @csrf
        @if ($editing)
            @method('PUT')
        @endif

        <div class="card">
            <div class="form__grid">
                <div class="field{{ $errors->has('platform') ? ' field--invalid' : '' }}">
                    <label class="field__label" for="platform">Platform <span>*</span></label>
                    <select class="field__control" id="platform" name="platform" required>
                        @foreach (\App\Models\SocialLink::platforms() as $value => $label)
                            <option value="{{ $value }}" @selected(old('platform', $socialLink->platform) === $value)>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                    @include('admin.partials.error', ['field' => 'platform'])
                </div>

                <div class="field{{ $errors->has('label') ? ' field--invalid' : '' }}">
                    <label class="field__label" for="label">Label</label>
                    <input class="field__control" id="label" name="label" type="text"
                           placeholder="Leave blank to use the platform name"
                           value="{{ old('label', $socialLink->label) }}">
                    @include('admin.partials.error', ['field' => 'label'])
                </div>

                <div class="field field--full{{ $errors->has('url') ? ' field--invalid' : '' }}">
                    <label class="field__label" for="url">URL</label>
                    <input class="field__control" id="url" name="url" type="text"
                           placeholder="https://…"
                           value="{{ old('url', $socialLink->url) }}">
                    <p class="field__hint">
                        Email uses <code>mailto:</code>, phone uses <code>tel:</code>, and WhatsApp uses the
                        number on its own (for example <code>923001234567</code>) — the full wa.me link is
                        built for you. A blank URL means no icon is rendered.
                    </p>
                    @include('admin.partials.error', ['field' => 'url'])
                </div>

                <div class="field field--full">
                    <label class="checkbox">
                        <input type="checkbox" name="is_visible" value="1"
                               @checked(old('is_visible', $socialLink->exists ? $socialLink->is_visible : true))>
                        Show this link on the site
                    </label>
                </div>
            </div>
        </div>

        <div class="form__actions">
            <button class="btn btn--primary" type="submit">{{ $editing ? 'Save changes' : 'Add link' }}</button>
            <a class="btn btn--ghost" href="{{ route('admin.social-links.index') }}">Cancel</a>
        </div>
    </form>
</x-layouts.admin>
