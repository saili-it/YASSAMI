<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

/**
 * Crée (ou promeut) un compte administrateur. Aucun mot de passe par défaut.
 *   docker compose exec app php artisan yassami:create-admin
 */
#[Signature('yassami:create-admin {--name=} {--email=}')]
#[Description('Crée ou met à jour un compte administrateur YASSAMI')]
class CreateAdmin extends Command
{
    public function handle(): int
    {
        $name = $this->option('name') ?: text('Nom', required: true);
        $email = $this->option('email') ?: text('Adresse e-mail', required: true);
        $password = password('Mot de passe (12 caractères minimum)', required: true);
        $confirmation = password('Confirmez le mot de passe', required: true);

        $validator = Validator::make(
            ['name' => $name, 'email' => $email, 'password' => $password, 'password_confirmation' => $confirmation],
            [
                'name' => ['required', 'string', 'max:120'],
                'email' => ['required', 'email', 'max:190'],
                'password' => ['required', 'string', 'min:12', 'confirmed'],
            ],
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $user = User::firstOrNew(['email' => strtolower($email)]);
        $existed = $user->exists;
        $user->forceFill(['name' => $name, 'password' => $password, 'is_admin' => true])->save();

        $this->info($existed
            ? "Compte {$user->email} mis à jour et promu administrateur."
            : "Administrateur {$user->email} créé. Connexion : ".route('admin.login'));

        return self::SUCCESS;
    }
}
