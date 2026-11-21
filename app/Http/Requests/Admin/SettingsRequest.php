<?php

namespace App\Http\Requests\Admin;

use App\Support\Phone;
use Illuminate\Foundation\Http\FormRequest;

class SettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->is_admin;
    }

    protected function prepareForValidation(): void
    {
        $trimmed = collect(['contact_phone', 'contact_email', 'contact_address', 'instagram_url', 'facebook_url', 'tiktok_url'])
            ->mapWithKeys(fn (string $key) => [$key => trim((string) $this->input($key)) ?: null])
            ->all();

        // Format attendu par wa.me : 212XXXXXXXXX
        $this->merge($trimmed + ['whatsapp_number' => Phone::toInternational($this->input('whatsapp_number'))]);
    }

    public function rules(): array
    {
        return [
            'whatsapp_number' => ['nullable', 'regex:/^212[5-7]\d{8}$/'],
            'contact_phone' => ['nullable', 'string', 'max:30', 'regex:/^[\d\s+().-]+$/'],
            'contact_email' => ['nullable', 'email:rfc', 'max:150'],
            'contact_address' => ['nullable', 'string', 'max:255'],
            'instagram_url' => ['nullable', 'url:https', 'max:255'],
            'facebook_url' => ['nullable', 'url:https', 'max:255'],
            'tiktok_url' => ['nullable', 'url:https', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'whatsapp_number.regex' => 'Numéro WhatsApp marocain invalide. Exemples acceptés : 06 12 34 56 78 ou +212 6 12 34 56 78.',
            'contact_phone.regex' => 'Le téléphone ne peut contenir que des chiffres, des espaces et les caractères + ( ) . -',
            '*.url' => 'Saisissez une adresse complète commençant par https://',
        ];
    }

    public function attributes(): array
    {
        return [
            'whatsapp_number' => 'numéro WhatsApp',
            'contact_phone' => 'téléphone',
            'contact_email' => 'e-mail',
            'contact_address' => 'adresse',
            'instagram_url' => 'lien Instagram',
            'facebook_url' => 'lien Facebook',
            'tiktok_url' => 'lien TikTok',
        ];
    }
}
