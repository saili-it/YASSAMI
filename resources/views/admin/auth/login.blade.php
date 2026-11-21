<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="robots" content="noindex, nofollow">
    <title>Connexion · Admin {{ config('yassami.brand') }}</title>
    @include('layouts.partials.favicons')
    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-svh bg-paper">
    <div class="grid min-h-svh lg:grid-cols-2">
        {{-- Visuel de marque (desktop) --}}
        <div class="relative hidden overflow-hidden bg-ink lg:block" aria-hidden="true">
            <img src="{{ asset('images/hero/hero-training.webp') }}" alt="" class="absolute inset-0 size-full object-cover opacity-70">
            <div class="absolute inset-0 bg-gradient-to-t from-ink via-ink/30 to-ink/60"></div>
            <div class="relative flex h-full flex-col justify-between p-12 text-paper">
                <x-logo tone="light" class="h-24 self-start" />
                <p class="display text-7xl italic">Gérez<br><span class="text-outline">votre</span><br>boutique<span class="text-accent">.</span></p>
            </div>
        </div>

        {{-- Formulaire --}}
        <div class="flex items-center justify-center px-4 py-12 sm:px-8">
            <div class="w-full max-w-sm">
                <x-logo class="h-16 lg:hidden" />
                <p class="eyebrow mt-10 lg:mt-0">Espace administrateur</p>
                <h1 class="display mt-2 text-5xl">Connexion</h1>

                @if (session('status'))
                    <p class="mt-6 border border-success/30 bg-success/10 px-4 py-3 text-sm font-medium text-success" role="status">{{ session('status') }}</p>
                @endif

                <form method="POST" action="{{ route('admin.login.store') }}" class="mt-8 space-y-5" novalidate>
                    @csrf
                    <div>
                        <label for="email" class="field-label">Adresse e-mail</label>
                        <input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="username" required autofocus
                               @class(['field', 'field-invalid' => $errors->has('email')]) aria-describedby="email-error">
                        @error('email')<p id="email-error" class="field-error">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="password" class="field-label">Mot de passe</label>
                        <input id="password" name="password" type="password" autocomplete="current-password" required
                               @class(['field', 'field-invalid' => $errors->has('password')]) aria-describedby="password-error">
                        @error('password')<p id="password-error" class="field-error">{{ $message }}</p>@enderror
                    </div>
                    <label class="flex items-center gap-2.5 text-sm">
                        <input type="checkbox" name="remember" value="1" class="size-4 accent-ink"> Rester connecté
                    </label>
                    <button type="submit" class="btn btn-primary btn-lg w-full">Se connecter <x-icon name="arrow-right" class="size-4" /></button>
                </form>

                <a href="{{ route('home') }}" class="mt-8 inline-flex items-center gap-1.5 text-xs font-semibold uppercase tracking-[0.12em] text-muted hover:text-ink">
                    <x-icon name="chevron-left" class="size-4" /> Retour à la boutique
                </a>
            </div>
        </div>
    </div>
</body>
</html>
