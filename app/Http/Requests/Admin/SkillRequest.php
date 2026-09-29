<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SkillRequest extends FormRequest
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
            'skill_category_id' => ['required', 'integer', 'exists:skill_categories,id'],
            'name' => ['required', 'string', 'max:80'],
            // Level is optional: skills without one render as plain tags.
            'level' => ['nullable', 'integer', 'min:1', 'max:100'],
            'is_visible' => ['nullable', 'boolean'],
        ];
    }

    public function prepareForValidation(): void
    {
        // An unchecked checkbox is absent from the payload, so the flag is
        // resolved explicitly to make clearing it work.
        $this->merge([
            'is_visible' => $this->boolean('is_visible'),
            'level' => $this->input('level') === '' || $this->input('level') === null
                ? null
                : $this->input('level'),
        ]);
    }
}
