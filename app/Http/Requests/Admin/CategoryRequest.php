<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->is_admin;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => trim((string) $this->input('name')),
            'slug' => $this->filled('slug') ? str($this->input('slug'))->slug()->toString() : null,
            'is_active' => $this->boolean('is_active'),
            'remove_image' => $this->boolean('remove_image'),
            'sort_order' => $this->input('sort_order') === null || $this->input('sort_order') === '' ? 0 : $this->input('sort_order'),
        ]);
    }

    public function rules(): array
    {
        $category = $this->route('category');

        return [
            'name' => ['required', 'string', 'max:120'],
            'slug' => ['nullable', 'string', 'max:140', Rule::unique('categories', 'slug')->ignore($category)],
            'description' => ['nullable', 'string', 'max:1000'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'remove_image' => ['boolean'],
            'is_active' => ['boolean'],
            'sort_order' => ['integer', 'min:0', 'max:9999'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nom',
            'slug' => 'adresse (slug)',
            'description' => 'description',
            'image' => 'image',
            'sort_order' => 'ordre d’affichage',
        ];
    }
}
