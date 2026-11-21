<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Database\QueryException;

/**
 * Paramètres de la boutique gérés depuis l'administration (/admin/parametres).
 *
 * Les valeurs enregistrées en base remplacent celles du .env dans config('yassami.*').
 * Dès qu'un champ a été enregistré depuis l'admin, c'est l'admin qui fait foi,
 * même s'il est vide (un champ vide masque l'information sur le site).
 * Le reste du code continue de lire uniquement config('yassami.*').
 */
class SiteSettings
{
    /** Clé en base => clé de configuration. */
    public const MAP = [
        'whatsapp_number' => 'yassami.whatsapp_number',
        'delivery_fee' => 'yassami.delivery_fee',
        'contact_phone' => 'yassami.contact.phone',
        'contact_email' => 'yassami.contact.email',
        'contact_address' => 'yassami.contact.address',
        'instagram_url' => 'yassami.social.instagram',
        'facebook_url' => 'yassami.social.facebook',
        'tiktok_url' => 'yassami.social.tiktok',
    ];

    private bool $applied = false;

    /** Applique les valeurs enregistrées sur la configuration (une seule requête SQL). */
    public function apply(): void
    {
        if ($this->applied) {
            return;
        }
        $this->applied = true;

        try {
            $stored = Setting::query()->whereIn('key', array_keys(self::MAP))->pluck('value', 'key');
        } catch (QueryException) {
            return; // Table absente (migration pas encore jouée) : on garde le .env.
        }

        foreach ($stored as $key => $value) {
            $value = trim((string) $value);
            config([self::MAP[$key] => $key === 'whatsapp_number' ? $value : ($value === '' ? null : $value)]);
        }

        // Un réseau social vide ne doit pas afficher d'icône.
        config(['yassami.social' => array_filter((array) config('yassami.social'))]);
    }

    /** Valeurs actuellement en vigueur, pour pré-remplir le formulaire. */
    public function current(): array
    {
        $this->apply();

        return collect(self::MAP)->map(fn (string $configKey) => (string) config($configKey, ''))->all();
    }

    /** Enregistre tous les champs du formulaire (vide = information masquée). */
    public function save(array $values): void
    {
        foreach (array_keys(self::MAP) as $key) {
            Setting::updateOrCreate(['key' => $key], ['value' => trim((string) ($values[$key] ?? ''))]);
        }

        $this->applied = false;
        $this->apply();
    }
}
