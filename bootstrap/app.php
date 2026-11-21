<?php

use App\Http\Middleware\ApplySiteSettings;
use App\Http\Middleware\EnsureUserIsAdmin;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias(['admin' => EnsureUserIsAdmin::class]);

        // En production le site est derrière un proxy HTTPS (Caddy ou Nginx de l'hôte) :
        // on lui fait confiance pour l'IP du client et le schéma https. Le conteneur
        // « web » n'écoute que sur 127.0.0.1, il n'est donc joignable que via ce proxy.
        $middleware->trustProxies(at: '*');

        // Numéro WhatsApp, coordonnées et réseaux gérés depuis /admin/parametres.
        $middleware->web(append: [ApplySiteSettings::class]);

        // Le seul espace authentifié est l'administration.
        $middleware->redirectGuestsTo(fn () => route('admin.login'));
        $middleware->redirectUsersTo(fn () => route('admin.dashboard'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
