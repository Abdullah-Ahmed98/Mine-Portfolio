<?php

namespace App\Http\Requests\Admin;

use App\Models\SocialLink;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SocialLinkRequest extends FormRequest
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
            'platform' => ['required', 'string', 'max:40', Rule::in(array_keys(SocialLink::platforms()))],
            'label' => ['nullable', 'string', 'max:60'],
            // Left blank on purpose for platforms that are not used yet: a link
            // without a URL never renders an icon on the site.
            'url' => ['nullable', 'string', 'max:300', 'url:http,https,mailto,tel'],
            'is_visible' => ['nullable', 'boolean'],
        ];
    }

    public function prepareForValidation(): void
    {
        // An unchecked checkbox is absent from the payload, so the flag is
        // resolved explicitly to make clearing it work.
        $this->merge(['is_visible' => $this->boolean('is_visible')]);
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'url.url' => 'Enter a full URL, including https:// (an email link should start with mailto:).',
        ];
    }
}
