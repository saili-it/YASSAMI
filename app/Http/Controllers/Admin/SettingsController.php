<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SettingsRequest;
use App\Services\SiteSettings;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class SettingsController extends Controller
{
    public function __construct(private readonly SiteSettings $settings) {}

    public function edit(): View
    {
        return view('admin.settings', ['values' => $this->settings->current()]);
    }

    public function update(SettingsRequest $request): RedirectResponse
    {
        $this->settings->save($request->validated());

        return redirect()->route('admin.settings.edit')->with('status', 'Paramètres enregistrés. Ils sont déjà appliqués sur la boutique.');
    }
}
