<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
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
            'full_name' => ['required', 'string', 'max:120'],
            'title' => ['required', 'string', 'max:180'],
            'role_short' => ['nullable', 'string', 'max:180'],
            'short_intro' => ['nullable', 'string', 'max:600'],
            'full_description' => ['nullable', 'string', 'max:6000'],
            'email' => ['nullable', 'email', 'max:180'],
            'phone' => ['nullable', 'string', 'max:40'],
            'location' => ['nullable', 'string', 'max:120'],
            'years_experience' => ['nullable', 'string', 'max:20'],
            'availability_text' => ['nullable', 'string', 'max:120'],
            'availability_status' => ['nullable', 'boolean'],

            'profile_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
            'remove_image' => ['nullable', 'boolean'],

            'cv' => ['nullable', 'file', 'mimes:pdf', 'max:4096'],
            'remove_cv' => ['nullable', 'boolean'],
        ];
    }

    public function prepareForValidation(): void
    {
        // Unchecked checkboxes are absent from the payload, so the flags are
        // resolved explicitly to make clearing them work.
        $this->merge([
            'availability_status' => $this->boolean('availability_status'),
            'remove_image' => $this->boolean('remove_image'),
            'remove_cv' => $this->boolean('remove_cv'),
        ]);
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'full_name' => 'full name',
            'profile_image' => 'profile image',
            'cv' => 'resume',
        ];
    }
}
