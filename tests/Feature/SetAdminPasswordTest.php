<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SetAdminPasswordTest extends TestCase
{
    use RefreshDatabase;

    private const CURRENT = 'Jz5WDPYeuiAMt9kUgaRF';

    private function admin(): User
    {
        return User::factory()->create([
            'email' => 'admin@portfolio.test',
            'password' => self::CURRENT,
        ]);
    }

    public function test_it_stores_a_hashed_password_that_verifies(): void
    {
        $user = $this->admin();

        $this->artisan('admin:password', ['--email' => $user->email])
            ->expectsQuestion('New password', 'FreshPassw0rd2026')
            ->expectsQuestion('Confirm password', 'FreshPassw0rd2026')
            ->assertSuccessful();

        $user->refresh();

        $this->assertTrue(Hash::check('FreshPassw0rd2026', $user->password));
        $this->assertFalse(Hash::check(self::CURRENT, $user->password));
    }

    public function test_it_never_stores_the_password_in_plaintext(): void
    {
        $user = $this->admin();

        $this->artisan('admin:password', ['--email' => $user->email])
            ->expectsQuestion('New password', 'FreshPassw0rd2026')
            ->expectsQuestion('Confirm password', 'FreshPassw0rd2026')
            ->assertSuccessful();

        $stored = $user->fresh()->password;

        $this->assertNotSame('FreshPassw0rd2026', $stored);
        $this->assertStringStartsWith('$2y$', $stored);
    }

    public function test_it_changes_nothing_when_the_two_answers_differ(): void
    {
        $user = $this->admin();

        $this->artisan('admin:password', ['--email' => $user->email])
            ->expectsQuestion('New password', 'FreshPassw0rd2026')
            ->expectsQuestion('Confirm password', 'SomethingElse2026')
            ->assertFailed();

        $this->assertTrue(Hash::check(self::CURRENT, $user->fresh()->password));
    }

    public function test_it_rejects_a_password_that_is_too_short(): void
    {
        $user = $this->admin();

        $this->artisan('admin:password', ['--email' => $user->email])
            ->expectsQuestion('New password', 'short')
            ->expectsQuestion('Confirm password', 'short')
            ->assertFailed();

        $this->assertTrue(Hash::check(self::CURRENT, $user->fresh()->password));
    }

    public function test_it_rejects_a_password_with_no_digits(): void
    {
        $user = $this->admin();

        $this->artisan('admin:password', ['--email' => $user->email])
            ->expectsQuestion('New password', 'onlyletterslongenough')
            ->expectsQuestion('Confirm password', 'onlyletterslongenough')
            ->assertFailed();

        $this->assertTrue(Hash::check(self::CURRENT, $user->fresh()->password));
    }

    public function test_it_refuses_to_reuse_the_current_password(): void
    {
        $user = $this->admin();

        $this->artisan('admin:password', ['--email' => $user->email])
            ->expectsQuestion('New password', self::CURRENT)
            ->expectsQuestion('Confirm password', self::CURRENT)
            ->assertFailed();

        $this->assertTrue(Hash::check(self::CURRENT, $user->fresh()->password));
    }

    public function test_it_fails_for_an_email_that_has_no_account(): void
    {
        $this->admin();

        $this->artisan('admin:password', ['--email' => 'nobody@example.test'])
            ->assertFailed();
    }

    public function test_it_defaults_to_the_only_account_when_no_email_is_given(): void
    {
        $user = $this->admin();

        $this->artisan('admin:password')
            ->expectsQuestion('New password', 'FreshPassw0rd2026')
            ->expectsQuestion('Confirm password', 'FreshPassw0rd2026')
            ->assertSuccessful();

        $this->assertTrue(Hash::check('FreshPassw0rd2026', $user->fresh()->password));
    }
}
