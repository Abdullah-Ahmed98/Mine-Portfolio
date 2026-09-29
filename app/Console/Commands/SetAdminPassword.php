<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password as PasswordRule;

/**
 * Changes an admin account's password.
 *
 * The admin panel has no password screen of its own, and the alternative is
 * pasting a password into a shell command, where it stays in the scrollback and
 * the history file. secret() keeps the new value out of both: it is read from a
 * hidden field and never echoed.
 */
class SetAdminPassword extends Command
{
    /**
     * @var string
     */
    protected $signature = 'admin:password
        {--email= : Account to update. Defaults to the only user, or asks when there are several.}
        {--new-email= : Also change the account\'s email address to this one.}';

    /**
     * @var string
     */
    protected $description = 'Set a new password, and optionally a new email, for an admin account';

    public function handle(): int
    {
        $user = $this->resolveUser();

        if (! $user instanceof User) {
            return self::FAILURE;
        }

        $this->newLine();
        $this->components->info("Changing the password for {$user->email}");

        $password = $this->askForPassword($user);

        if (! is_string($password)) {
            return self::FAILURE;
        }

        /*
         * The model casts the column to 'hashed', so the plaintext is assigned and
         * the cast hashes it on save. Assigning a hash here instead would be
         * hashed a second time and lock the account out.
         */
        $user->forceFill(['password' => $password]);

        if (! $this->applyNewEmail($user)) {
            return self::FAILURE;
        }

        $user->save();

        $this->newLine();
        $this->components->info('Password updated.');
        $this->components->twoColumnDetail('Account', $user->email);
        $this->components->twoColumnDetail('Stored as', 'bcrypt hash; the plaintext was not written anywhere');

        return self::SUCCESS;
    }

    /**
     * Move the account to a different address, so the login no longer depends on
     * a placeholder like admin@portfolio.test.
     *
     * Nothing is saved until the password has also passed its checks, so a bad
     * address here cannot leave the account half-updated.
     */
    private function applyNewEmail(User $user): bool
    {
        $new = trim((string) $this->option('new-email'));

        if ($new === '') {
            return true;
        }

        $validator = Validator::make(
            ['email' => $new],
            [
                'email' => [
                    'required',
                    'email',
                    // Unique across every account except the one being edited, so
                    // re-saving the same address is not treated as a collision.
                    Rule::unique('users', 'email')->ignore($user->id),
                ],
            ],
            ['email.unique' => 'Another account already uses that email address.']
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $message) {
                $this->components->error($message);
            }

            return false;
        }

        $previous = $user->email;

        $user->forceFill(['email' => $new]);

        $this->components->twoColumnDetail('Email', "{$previous} -> {$new}");

        return true;
    }

    private function resolveUser(): ?User
    {
        $email = $this->option('email');

        if (filled($email)) {
            $user = User::where('email', $email)->first();

            if (! $user instanceof User) {
                $this->components->error("No account with the email {$email}.");

                return null;
            }

            return $user;
        }

        $users = User::query()->orderBy('id')->get();

        if ($users->isEmpty()) {
            $this->components->error('There are no user accounts. Run: php artisan db:seed');

            return null;
        }

        if ($users->count() === 1) {
            return $users->first();
        }

        $answer = (string) $this->ask(
            'Email of the account to update',
            $users->first()->email
        );

        return $users->firstWhere('email', $answer);
    }

    /**
     * Ask twice, so a typo cannot lock the account out, and reject anything weak
     * before it becomes the new credential.
     */
    private function askForPassword(User $user): ?string
    {
        $first = (string) $this->secret('New password');
        $confirmation = (string) $this->secret('Confirm password');

        if ($first !== $confirmation) {
            $this->components->error('The two passwords did not match. Nothing was changed.');

            return null;
        }

        $validator = Validator::make(
            ['password' => $first],
            ['password' => ['required', 'string', PasswordRule::min(12)->letters()->numbers()]],
            ['password.required' => 'The password cannot be empty.']
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $message) {
                $this->components->error($message);
            }

            return null;
        }

        if (Hash::check($first, $user->password)) {
            $this->components->error('That is already the current password. Nothing was changed.');

            return null;
        }

        return $first;
    }
}
