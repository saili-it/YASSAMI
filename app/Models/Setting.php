<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/** Paramètre de la boutique (clé → valeur), géré par App\Services\SiteSettings. */
#[Fillable(['key', 'value'])]
class Setting extends Model {}
