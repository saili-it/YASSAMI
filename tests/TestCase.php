<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    /**
     * Garde-fou : les tests utilisent RefreshDatabase, qui VIDE la base.
     * On refuse de démarrer si la connexion n'est pas SQLite en mémoire
     * (ex. variables Docker DB_* qui prendraient le pas sur phpunit.xml).
     */
    public function createApplication()
    {
        $app = parent::createApplication();

        $default = $app['config']->get('database.default');
        $database = $app['config']->get("database.connections.{$default}.database");

        if ($default !== 'sqlite' || $database !== ':memory:' || ! $app->environment('testing')) {
            throw new RuntimeException(sprintf(
                'Tests interrompus : base « %s/%s » (env %s) au lieu de sqlite/:memory:. Aucune donnée n’a été touchée.',
                $default, $database, $app->environment()
            ));
        }

        return $app;
    }
}
