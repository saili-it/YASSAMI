<?php

namespace App\Http\Requests\Admin;

use App\Enums\OrderStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class OrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->is_admin;
    }

    protected function prepareForValidation(): void
    {
        $fee = trim((string) $this->input('delivery_fee'));

        $this->merge([
            // « 35,50 » ou « 35.50 » ; vide = pas encore convenu.
            'delivery_fee' => $fee === '' ? null : str_replace([' ', ','], ['', '.'], $fee),
            'admin_note' => trim((string) $this->input('admin_note')) ?: null,
        ]);
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::enum(OrderStatus::class)],
            'delivery_fee' => ['nullable', 'numeric', 'min:0', 'max:99999'],
            'admin_note' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'status' => 'statut',
            'delivery_fee' => 'frais de livraison',
            'admin_note' => 'note interne',
        ];
    }
}
