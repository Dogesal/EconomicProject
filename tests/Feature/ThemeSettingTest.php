<?php

namespace Tests\Feature;

use App\Domain\Models\Setting;
use App\Support\AppTheme;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ThemeSettingTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_theme_defaults_to_following_the_system(): void
    {
        $this->assertSame('system', app(AppTheme::class)->resolve());
    }

    public function test_the_theme_can_be_switched_to_dark(): void
    {
        $this->put(route('settings.theme'), ['theme' => 'dark'])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame('dark', app(AppTheme::class)->resolve());
    }

    public function test_the_theme_can_be_switched_back_to_system(): void
    {
        Setting::put(AppTheme::SETTING_KEY, 'dark');

        $this->put(route('settings.theme'), ['theme' => 'system'])->assertRedirect();

        $this->assertSame('system', app(AppTheme::class)->resolve());
    }

    public function test_an_invalid_theme_is_rejected(): void
    {
        $this->put(route('settings.theme'), ['theme' => 'solarized'])
            ->assertSessionHasErrors('theme');

        $this->assertSame('system', app(AppTheme::class)->resolve());
    }

    public function test_the_theme_is_shared_with_every_inertia_page(): void
    {
        Setting::put(AppTheme::SETTING_KEY, 'dark');

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('theme', 'dark'));
    }
}
