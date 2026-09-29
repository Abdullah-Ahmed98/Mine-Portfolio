<x-layouts.admin title="Settings">
    <div class="topbar">
        <div>
            <h1>Settings</h1>
            <p>Headings, labels and SEO copy. Changes apply to the whole site immediately.</p>
        </div>
    </div>

    @if ($errors->any())
        <div class="alert alert--error">Please fix the highlighted fields below.</div>
    @endif

    <form class="form" method="POST" action="{{ route('admin.settings.update') }}">
        @csrf
        @method('PUT')

        @foreach ($groups as $group => $label)
            @if (($grouped[$group] ?? collect())->isNotEmpty())
                <div class="card">
                    <p class="card__title">{{ $label }}</p>
                    <p class="card__hint">Used on the public site.</p>

                    <div class="form__grid">
                        @foreach ($grouped[$group] as $setting)
                            <div class="field{{ $errors->has("settings.{$setting->key}") ? ' field--invalid' : '' }}{{ $setting->label === null ? ' field--full' : '' }}">
                                <label class="field__label" for="setting-{{ $setting->key }}">
                                    {{ $setting->label ?: $setting->key }}
                                </label>

                                @if (str_contains($setting->key, 'description') || str_contains($setting->key, 'body'))
                                    @php
                                        // Grown to fit, so a long passage can be read and edited
                                        // without scrolling inside a three-line box.
                                        $text = old("settings.{$setting->key}", $setting->value) ?? '';
                                        $rows = min(16, max(3, count(preg_split('/\R/', (string) $text)) + 1));
                                    @endphp

                                    <textarea class="field__control" rows="{{ $rows }}"
                                              id="setting-{{ $setting->key }}"
                                              name="settings[{{ $setting->key }}]">{{ $text }}</textarea>

                                    <p class="field__hint">Leave a blank line between paragraphs.</p>
                                @else
                                    <input class="field__control" type="text"
                                           id="setting-{{ $setting->key }}"
                                           name="settings[{{ $setting->key }}]"
                                           value="{{ old("settings.{$setting->key}", $setting->value) }}">
                                @endif

                                <p class="field__hint"><code>{{ $setting->key }}</code></p>
                                @include('admin.partials.error', ['field' => "settings.{$setting->key}"])
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        @endforeach

        <div class="form__actions">
            <button class="btn btn--primary" type="submit">Save settings</button>
        </div>
    </form>
</x-layouts.admin>
