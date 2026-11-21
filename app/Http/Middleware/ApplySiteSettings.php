<?php

namespace App\Http\Middleware;

use App\Services\SiteSettings;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Charge les paramètres de l'administration dans config('yassami.*') pour chaque requête web. */
class ApplySiteSettings
{
    public function __construct(private readonly SiteSettings $settings) {}

    public function handle(Request $request, Closure $next): Response
    {
        $this->settings->apply();

        return $next($request);
    }
}
