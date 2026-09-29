<?php

namespace App\Http\Requests\Admin;

use App\Models\LatestWorkItem;
use App\Support\TagList;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class LatestWorkItemRequest extends FormRequest
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
            'latest_work_category_id' => [
                // The type is a label on the card, so an item without one still
                // renders. Nothing is hidden by leaving it unset.
                'nullable',
                'integer',
                'exists:latest_work_categories,id',
            ],
            'short_description' => ['nullable', 'string', 'max:600'],
            'details' => ['nullable', 'string', 'max:4000'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
            'remove_image' => ['nullable', 'boolean'],
            'project_url' => ['nullable', 'string', 'max:300', 'url:http,https'],
            'technologies' => ['nullable', 'array', 'max:20'],
            'technologies.*' => ['string', 'max:60'],
            'is_featured' => ['nullable', 'boolean'],
            'is_visible' => ['nullable', 'boolean'],
        ];
    }

    public function prepareForValidation(): void
    {
        $this->merge([
            'technologies' => TagList::parse($this->input('technologies')),
            // An unchecked checkbox is absent from the payload, so both flags are
            // resolved explicitly to make clearing them work. A brand new item
            // starts visible, matching the column default and the create form.
            'is_featured' => $this->boolean('is_featured'),
            'is_visible' => $this->shouldBeVisible(),
            'remove_image' => $this->boolean('remove_image'),
        ]);
    }

    /**
     * The form always sends the checkbox, so an absent key means the request did
     * not come from that form. A new item starts visible, and an update that
     * says nothing about visibility keeps whatever the item already had rather
     * than quietly pulling it off the site.
     */
    private function shouldBeVisible(): bool
    {
        if ($this->has('is_visible')) {
            return $this->boolean('is_visible');
        }

        if ($this->routeIs('admin.latest-work.store')) {
            return true;
        }

        $item = $this->route('latestWorkItem');

        return $item instanceof LatestWorkItem ? $item->is_visible : false;
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Give the item a title — that is what shows on the card.',
        ];
    }
}
