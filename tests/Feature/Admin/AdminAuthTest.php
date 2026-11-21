<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAuthTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $user = User::factory()->create(['email' => 'admin@example.test']);
        $user->forceFill(['is_admin' => true])->save();

        return $user;
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get(route('admin.dashboard'))->assertRedirect(route('admin.login'));
        $this->get(route('admin.products.index'))->assertRedirect(route('admin.login'));
        $this->get(route('admin.login'))->assertOk()->assertSee('Connexion');
    }

    public function test_admin_can_log_in_and_out(): void
    {
        $this->admin();

        $this->post(route('admin.login.store'), ['email' => 'admin@example.test', 'password' => 'password'])
            ->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticated();
        $this->get(route('admin.dashboard'))->assertOk()->assertSee('Tableau de bord');

        $this->post(route('admin.logout'))->assertRedirect(route('admin.login'));
        $this->assertGuest();
    }

    public function test_non_admin_users_cannot_log_in_or_access_admin(): void
    {
        $user = User::factory()->create(['email' => 'client@example.test']);

        $this->post(route('admin.login.store'), ['email' => 'client@example.test', 'password' => 'password'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();

        $this->actingAs($user)->get(route('admin.dashboard'))->assertRedirect(route('admin.login'));
        $this->assertGuest();
    }

    public function test_wrong_password_is_rejected_and_login_is_throttled(): void
    {
        $this->admin();

        foreach (range(1, 5) as $_) {
            $this->post(route('admin.login.store'), ['email' => 'admin@example.test', 'password' => 'wrong'])
                ->assertSessionHasErrors(['email' => trans('auth.failed')]);
        }

        $this->post(route('admin.login.store'), ['email' => 'admin@example.test', 'password' => 'password'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_create_admin_command(): void
    {
        $this->artisan('yassami:create-admin', ['--name' => 'Yassami', '--email' => 'Boss@Example.test'])
            ->expectsQuestion('Mot de passe (12 caractères minimum)', 'un-mot-de-passe-solide')
            ->expectsQuestion('Confirmez le mot de passe', 'un-mot-de-passe-solide')
            ->assertSuccessful();

        $user = User::where('email', 'boss@example.test')->first();
        $this->assertTrue($user->is_admin);

        $this->artisan('yassami:create-admin', ['--name' => 'X', '--email' => 'x@example.test'])
            ->expectsQuestion('Mot de passe (12 caractères minimum)', 'court')
            ->expectsQuestion('Confirmez le mot de passe', 'court')
            ->assertFailed();
    }
}
