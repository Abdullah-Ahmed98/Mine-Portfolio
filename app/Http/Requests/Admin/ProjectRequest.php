<?php

namespace App\Http\Requests\Admin;

use App\Support\TagList;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProjectRequest extends FormRequest
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
            'title' => ['required', 'string', 'max:180'],
            'slug' => ['nullable', 'string', 'max:200', 'alpha_dash'],
            'project_category_id' => [
                /*
                 * Each category renders as its own showcase section, so a
                 * published project with no category would never appear on the
                 * site. Drafts may still be saved uncategorised.
                 */
                Rule::requiredIf(fn (): bool => $this->boolean('is_published')),
                'nullable',
                'integer',
                'exists:project_categories,id',
            ],
            'client_name' => ['nullable', 'string', 'max:140'],
            'client_role' => ['nullable', 'string', 'max:140'],
            'short_description' => ['nullable', 'string', 'max:600'],
            'full_description' => ['nullable', 'string', 'max:8000'],
            'featured_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
            'remove_image' => ['nullable', 'boolean'],
            'project_url' => ['nullable', 'string', 'max:300', 'url:http,https'],
            'github_url' => ['nullable', 'string', 'max:300', 'url:http,https'],
            'completed_at' => ['nullable', 'date'],
            'technologies' => ['nullable', 'array', 'max:40'],
            'technologies.*' => ['string', 'max:60'],
            'is_featured' => ['nullable', 'boolean'],
            'is_published' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'project_category_id.required' => 'Pick a section — a published project only shows on the site under a category.',
        ];
    }

    public function prepareForValidation(): void
    {
        $this->merge([
            'technologies' => TagList::parse($this->input('technologies')),
            // An unchecked checkbox is absent from the payload, so both flags
            // are resolved explicitly to make clearing them work. A brand new
            // project starts published, matching the column default and the
            // create form, which shows the box already ticked.
            'is_featured' => $this->boolean('is_featured'),
            'is_published' => $this->shouldPublish(),
            'remove_image' => $this->boolean('remove_image'),
        ]);
    }

    private function shouldPublish(): bool
    {
        if ($this->has('is_published')) {
            return $this->boolean('is_published');
        }

        return $this->routeIs('admin.projects.store');
    }
}
