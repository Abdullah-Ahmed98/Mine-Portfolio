<?php

namespace App\Http\Requests\Admin;

use App\Models\Setting;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'settings' => ['required', 'array'],
            'settings.*' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * Only keys that already exist may be written, so the form cannot invent
     * arbitrary setting rows.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $known = Setting::query()->pluck('key')->all();

            foreach (array_keys($this->input('settings', [])) as $key) {
                if (! in_array($key, $known, true)) {
                    $validator->errors()->add("settings.{$key}", 'Unknown setting.');
                }
            }
        });
    }

    /**
     * @return array<string, string|null>
     */
    public function values(): array
    {
        $values = [];

        foreach ($this->validated()['settings'] ?? [] as $key => $value) {
            $values[$key] = $value === null ? null : (string) $value;
        }

        return $values;
    }
}
