<?php

namespace App\Http\Requests\Admin;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    private const MAX_ATTEMPTS = 5;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    public function attributes(): array
    {
        return ['email' => 'adresse e-mail', 'password' => 'mot de passe'];
    }

    /** Authentifie un administrateur, avec limitation des tentatives (5 / minute par e-mail + IP). */
    public function authenticate(): void
    {
        $key = $this->throttleKey();

        if (RateLimiter::tooManyAttempts($key, self::MAX_ATTEMPTS)) {
            event(new Lockout($this));

            throw ValidationException::withMessages([
                'email' => trans('auth.throttle', ['seconds' => RateLimiter::availableIn($key)]),
            ]);
        }

        $credentials = $this->only('email', 'password') + ['is_admin' => true];

        if (! Auth::attempt($credentials, $this->boolean('remember'))) {
            RateLimiter::hit($key, 60);

            throw ValidationException::withMessages(['email' => trans('auth.failed')]);
        }

        RateLimiter::clear($key);
    }

    private function throttleKey(): string
    {
        return Str::transliterate(Str::lower((string) $this->input('email')).'|'.$this->ip());
    }
}
