<?php

namespace App\Http\Requests\Admin;

use App\Support\TagList;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ExperienceRequest extends FormRequest
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
            'company' => ['required', 'string', 'max:140'],
            'position' => ['required', 'string', 'max:160'],
            'location' => ['nullable', 'string', 'max:140'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'is_current' => ['nullable', 'boolean'],
            // Overrides the generated range, for entries like "2019 · 1 month".
            'date_label' => ['nullable', 'string', 'max:80'],
            'description' => ['nullable', 'string', 'max:2000'],
            'responsibilities' => ['nullable', 'array', 'max:40'],
            'responsibilities.*' => ['string', 'max:600'],
            'technologies' => ['nullable', 'array', 'max:40'],
            'technologies.*' => ['string', 'max:60'],
            'company_url' => ['nullable', 'string', 'max:300', 'url:http,https'],
        ];
    }

    public function prepareForValidation(): void
    {
        // An unchecked checkbox is absent from the payload, so the flag is
        // resolved explicitly to make clearing it work.
        $this->merge(['is_current' => $this->boolean('is_current')]);

        if ($this->boolean('is_current')) {
            $this->merge(['end_date' => null]);
        }

        // The tag inputs are typed as comma or newline separated text, but the
        // columns are cast to arrays, so they are normalised before validation.
        $this->merge([
            'technologies' => TagList::parse($this->input('technologies')),
            'responsibilities' => TagList::parse($this->input('responsibilities')),
        ]);
    }
}
