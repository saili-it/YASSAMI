<?php

namespace Tests\Feature\Admin;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminSettingsTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsAdmin(): static
    {
        $admin = User::factory()->create();
        $admin->forceFill(['is_admin' => true])->save();

        return $this->actingAs($admin);
    }

    private function payload(array $overrides = []): array
    {
        return $overrides + [
            'whatsapp_number' => '06 12 34 56 78',
            'contact_phone' => '05 22 00 00 00',
            'contact_email' => 'contact@example.com',
            'contact_address' => 'Casablanca',
            'instagram_url' => 'https://www.instagram.com/example',
            'facebook_url' => '',
            'tiktok_url' => '',
        ];
    }

    public function test_guests_and_non_admins_cannot_access_settings(): void
    {
        $this->get(route('admin.settings.edit'))->assertRedirect(route('admin.login'));
        $this->put(route('admin.settings.update'), $this->payload())->assertRedirect(route('admin.login'));

        $this->actingAs(User::factory()->create())->get(route('admin.settings.edit'))->assertRedirect(route('admin.login'));
        $this->assertSame(0, Setting::count());
    }

    public function test_settings_page_renders(): void
    {
        $this->actingAsAdmin()->get(route('admin.settings.edit'))->assertOk()->assertSee('Contact WhatsApp');
    }

    public function test_saving_normalizes_the_number_and_applies_it_to_the_storefront(): void
    {
        config(['yassami.whatsapp_number' => '']);

        $this->actingAsAdmin()->put(route('admin.settings.update'), $this->payload())
            ->assertRedirect(route('admin.settings.edit'))
            ->assertSessionHasNoErrors();

        $this->assertSame('212612345678', Setting::where('key', 'whatsapp_number')->value('value'));

        $this->get(route('admin.settings.edit'))->assertSee('+212 6 12 34 56 78');
        $this->get(route('home'))->assertOk()
            ->assertSee('https://wa.me/212612345678', false)
            ->assertSee('contact@example.com')
            ->assertSee('https://www.instagram.com/example', false)
            ->assertDontSee('facebook.com', false);
    }

    public function test_international_formats_are_accepted(): void
    {
        foreach (['+212 6 12 34 56 78', '00212612345678', '212-612-345-678', '07.12.34.56.78'] as $input) {
            $this->actingAsAdmin()->put(route('admin.settings.update'), $this->payload(['whatsapp_number' => $input]))
                ->assertSessionHasNoErrors();
        }

        $this->assertSame('212712345678', Setting::where('key', 'whatsapp_number')->value('value'));
    }

    public function test_invalid_values_are_rejected(): void
    {
        $this->actingAsAdmin()->from(route('admin.settings.edit'))->put(route('admin.settings.update'), $this->payload([
            'whatsapp_number' => '12345',
            'contact_email' => 'pas-un-email',
            'instagram_url' => 'instagram.com/example',
        ]))->assertRedirect(route('admin.settings.edit'))
            ->assertSessionHasErrors(['whatsapp_number', 'contact_email', 'instagram_url']);

        $this->assertSame(0, Setting::count());
    }

    public function test_clearing_a_field_in_admin_overrides_env_and_hides_it(): void
    {
        config(['yassami.whatsapp_number' => '212600000000']); // valeur venant du .env

        $this->actingAsAdmin()->put(route('admin.settings.update'), $this->payload(['whatsapp_number' => '']))
            ->assertSessionHasNoErrors();

        $this->assertSame('', config('yassami.whatsapp_number'));
        $this->get(route('home'))->assertOk()->assertDontSee('wa.me/', false);
    }
}
