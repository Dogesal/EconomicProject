<?php

namespace Tests\Feature;

use App\Domain\Models\Account;
use App\Support\AppLock;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppLockTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_app_is_accessible_when_the_lock_is_disabled(): void
    {
        Account::factory()->create();

        $this->get(route('dashboard'))->assertOk();
    }

    public function test_protected_routes_redirect_to_the_lock_screen_when_locked(): void
    {
        app(AppLock::class)->setEnabled(true);

        $this->get(route('dashboard'))->assertRedirect(route('lock.show'));
        $this->get(route('transactions.index'))->assertRedirect(route('lock.show'));
    }

    public function test_the_lock_screen_renders_while_locked(): void
    {
        app(AppLock::class)->setEnabled(true);

        $this->get(route('lock.show'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Lock'));
    }

    public function test_unlocking_grants_access_for_the_session(): void
    {
        app(AppLock::class)->setEnabled(true);

        $this->post(route('lock.unlock'))->assertRedirect(route('dashboard'));

        // Same test client keeps the session, so the app is now reachable.
        $this->get(route('dashboard'))->assertOk();
    }

    public function test_disabling_the_lock_via_settings_turns_it_off(): void
    {
        app(AppLock::class)->setEnabled(true);

        // Must pass the lock first (as a real user would) to reach settings.
        $this->post(route('lock.unlock'));

        $this->put(route('settings.lock'), ['enabled' => false])->assertRedirect();

        $this->assertFalse(app(AppLock::class)->isEnabled());
        $this->get(route('dashboard'))->assertOk();
    }
}
