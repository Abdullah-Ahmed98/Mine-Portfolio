<?php

namespace Tests\Feature\Admin;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * The settings screen only accepts keys that already exist as rows, so each
     * test declares the keys it intends to write.
     */
    private function declare(array $values, string $group = 'navigation'): void
    {
        foreach ($values as $key => $value) {
            Setting::factory()->create([
                'key' => $key,
                'value' => $value,
                'group' => $group,
            ]);
        }
    }

    public function test_saving_known_settings_updates_their_values(): void
    {
        $this->declare([
            'nav.home' => 'Home',
            'footer.headline' => 'Let us talk',
        ]);

        $this->actingAs(User::factory()->create())
            ->from('/admin/settings')
            ->put('/admin/settings', [
                'settings' => [
                    'nav.home' => 'Start',
                    'footer.headline' => 'Available for new work',
                ],
            ])
            ->assertRedirect('/admin/settings')
            ->assertSessionHas('status')
            ->assertSessionHasNoErrors();

        $this->assertSame('Start', Setting::get('nav.home'));
        $this->assertSame('Available for new work', Setting::get('footer.headline'));
    }

    public function test_a_key_with_no_existing_row_is_rejected_and_not_stored(): void
    {
        $this->actingAs(User::factory()->create())
            ->from('/admin/settings')
            ->put('/admin/settings', [
                'settings' => ['footer.headline' => "Let's talk"],
            ])
            ->assertSessionHasErrors('settings.footer.headline');

        $this->assertDatabaseMissing('settings', ['key' => 'footer.headline']);
    }

    public function test_a_privileged_environment_key_cannot_be_written(): void
    {
        $this->actingAs(User::factory()->create())
            ->from('/admin/settings')
            ->put('/admin/settings', [
                'settings' => ['app.env' => 'production', 'db.password' => 'leaked'],
            ])
            ->assertSessionHasErrors(['settings.app.env', 'settings.db.password']);

        $this->assertDatabaseMissing('settings', ['key' => 'app.env']);
        $this->assertDatabaseMissing('settings', ['key' => 'db.password']);
    }

    /**
     * A rejected key must not leave the other values half written, so the save
     * is all or nothing.
     */
    public function test_an_unknown_key_rejects_the_whole_payload(): void
    {
        $this->declare(['nav.home' => 'Home']);

        $this->actingAs(User::factory()->create())
            ->from('/admin/settings')
            ->put('/admin/settings', [
                'settings' => [
                    'nav.home' => 'Start',
                    'db.password' => 'leaked',
                ],
            ])
            ->assertSessionHasErrors('settings.db.password');

        $this->assertDatabaseHas('settings', ['key' => 'nav.home', 'value' => 'Home']);
    }

    public function test_settings_saved_in_the_admin_appear_on_the_public_site(): void
    {
        // Both of these are rendered by the single page, so the test covers the
        // round trip from an admin save to what a visitor actually sees.
        $this->declare(['nav.about' => 'About'], 'navigation');
        $this->declare(['footer.headline' => 'Say hello'], 'footer');

        $this->actingAs(User::factory()->create())
            ->put('/admin/settings', [
                'settings' => [
                    'nav.about' => 'Background',
                    'footer.headline' => 'Available for new work',
                ],
            ])
            ->assertSessionHasNoErrors();

        $this->get('/')
            ->assertOk()
            ->assertSee('Background')
            ->assertSee('Available for new work');
    }

    public function test_writing_a_setting_directly_drops_the_cached_copy(): void
    {
        Setting::factory()->create(['key' => 'nav.home', 'value' => 'Home']);

        $this->assertSame('Home', Setting::get('nav.home'));

        Setting::put('nav.home', 'Start');

        $this->assertSame('Start', Setting::get('nav.home'));
    }

    public function test_get_falls_back_to_the_supplied_default_when_the_key_is_missing(): void
    {
        $this->assertSame('Fallback', Setting::get('nav.missing', 'Fallback'));
    }

    public function test_get_falls_back_to_the_default_when_the_stored_value_is_blank(): void
    {
        Setting::factory()->create(['key' => 'nav.home', 'value' => '']);

        $this->assertSame('Fallback', Setting::get('nav.home', 'Fallback'));
    }

    public function test_updating_a_setting_row_keeps_its_key_and_drops_the_cache(): void
    {
        $setting = Setting::factory()->create(['key' => 'nav.home', 'value' => 'Home']);

        $this->assertSame('Home', Setting::get('nav.home'));

        $setting->update(['value' => 'Start']);

        $this->assertSame('Start', Setting::get('nav.home'));
        $this->assertSame(1, Setting::query()->where('key', 'nav.home')->count());
    }

    public function test_the_settings_screen_renders_a_control_for_every_group(): void
    {
        $this->declare(['nav.home' => 'Home'], 'navigation');
        $this->declare(['seo.title' => 'A title'], 'seo');

        $this->actingAs(User::factory()->create())
            ->get('/admin/settings')
            ->assertOk()
            ->assertSee('name="settings[nav.home]"', escape: false)
            ->assertSee('name="settings[seo.title]"', escape: false);
    }

    public function test_the_settings_screen_renders_no_inputs_for_a_guest(): void
    {
        $this->declare(['nav.home' => 'Home']);

        $this->get('/admin/settings')
            ->assertRedirect(route('admin.login'));
    }
}
