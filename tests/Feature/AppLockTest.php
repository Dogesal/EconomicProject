<?php

namespace Tests\Feature;

use App\Domain\Models\Account;
use App\Domain\Models\Setting;
use App\Support\AppLock;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
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

    public function test_the_lock_cannot_be_enabled_without_a_backup_pin(): void
    {
        $this->put(route('settings.lock'), ['enabled' => true])
            ->assertSessionHasErrors('enabled');

        $this->assertFalse(app(AppLock::class)->isEnabled());
    }

    public function test_the_lock_can_be_enabled_once_a_pin_exists(): void
    {
        app(AppLock::class)->setPin('1234');

        $this->put(route('settings.lock'), ['enabled' => true])->assertRedirect();

        $this->assertTrue(app(AppLock::class)->isEnabled());
    }

    public function test_the_pin_is_stored_hashed_and_requires_confirmation(): void
    {
        $this->put(route('settings.pin'), ['pin' => '1234'])
            ->assertSessionHasErrors('pin');

        $this->put(route('settings.pin'), ['pin' => '1234', 'pin_confirmation' => '1234'])
            ->assertRedirect()
            ->assertSessionHas('success');

        $stored = Setting::get(AppLock::PIN_KEY);

        $this->assertNotSame('1234', $stored);
        $this->assertTrue(Hash::check('1234', $stored));
        $this->assertTrue(app(AppLock::class)->checkPin('1234'));
        $this->assertFalse(app(AppLock::class)->checkPin('9999'));
    }

    public function test_changing_the_pin_while_armed_requires_the_current_pin(): void
    {
        $lock = app(AppLock::class);
        $lock->setPin('1234');
        $lock->setEnabled(true);
        $this->post(route('lock.unlock'), ['pin' => '1234']);

        $this->put(route('settings.pin'), ['pin' => '5678', 'pin_confirmation' => '5678'])
            ->assertSessionHasErrors('current_pin');

        $this->put(route('settings.pin'), [
            'current_pin' => '1234',
            'pin' => '5678',
            'pin_confirmation' => '5678',
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertTrue($lock->checkPin('5678'));
    }

    public function test_unlocking_with_the_correct_pin_grants_access(): void
    {
        $lock = app(AppLock::class);
        $lock->setPin('1234');
        $lock->setEnabled(true);

        $this->post(route('lock.unlock'), ['pin' => '1234'])
            ->assertRedirect(route('dashboard'));

        $this->get(route('dashboard'))->assertOk();
    }

    public function test_unlocking_with_a_wrong_pin_keeps_the_app_locked(): void
    {
        $lock = app(AppLock::class);
        $lock->setPin('1234');
        $lock->setEnabled(true);

        $this->post(route('lock.unlock'), ['pin' => '0000'])
            ->assertSessionHasErrors('pin');

        $this->get(route('dashboard'))->assertRedirect(route('lock.show'));
    }

    public function test_relock_drops_the_session_unlock(): void
    {
        $lock = app(AppLock::class);
        $lock->setPin('1234');
        $lock->setEnabled(true);

        $this->post(route('lock.unlock'), ['pin' => '1234']);
        $this->get(route('dashboard'))->assertOk();

        $this->post(route('lock.relock'))->assertRedirect(route('lock.show'));

        $this->get(route('dashboard'))->assertRedirect(route('lock.show'));

        // Relock stays reachable even while locked (lifecycle calls).
        $this->post(route('lock.relock'))->assertRedirect(route('lock.show'));
    }
}
