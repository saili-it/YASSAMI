<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

final class Slug
{
    /**
     * Slug unique pour la table du modèle : « short-flex », puis « short-flex-2 », …
     * $ignoreId exclut l'enregistrement en cours d'édition.
     */
    public static function unique(string $modelClass, string $source, ?int $ignoreId = null): string
    {
        $base = Str::slug($source) ?: 'element';
        $slug = $base;
        $i = 2;

        /** @var Model $model */
        $model = new $modelClass;

        while ($model->newQuery()
            ->where('slug', $slug)
            ->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))
            ->exists()) {
            $slug = "{$base}-{$i}";
            $i++;
        }

        return $slug;
    }
}
