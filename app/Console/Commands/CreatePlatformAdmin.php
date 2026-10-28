<?php

namespace App\Console\Commands;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

/**
 * Create (or reset) a platform account on a server: the password is typed hidden, never stored in files.
 */
#[Signature('platform:admin {email : Login email} {--role=platform : developer or platform} {--name= : Display name}')]
#[Description('Create or update a developer / platform-owner account with a hidden password prompt')]
class CreatePlatformAdmin extends Command
{
    public function handle(): int
    {
        $role = $this->option('role');
        if (! in_array($role, ['developer', 'platform'], true)) {
            $this->error('--role must be developer or platform');

            return self::FAILURE;
        }

        $email = $this->argument('email');
        $password = $this->secret('Password (min 12 characters)');
        $confirm = $this->secret('Repeat password');

        $validator = Validator::make(
            ['email' => $email, 'password' => $password, 'password_confirmation' => $confirm],
            ['email' => ['required', 'email'], 'password' => ['required', 'confirmed', Password::min(12)->mixedCase()->numbers()]],
        );
        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $message) {
                $this->error($message);
            }

            return self::FAILURE;
        }

        $user = User::query()->firstOrNew(['email' => $email]);
        $user->forceFill([
            'name' => $this->option('name') ?: ($user->name ?: ($role === 'developer' ? 'Developer' : 'Platform owner')),
            'password' => $password,
            'role' => Role::from($role),
            'company_id' => null,
            'is_active' => true,
            'email_verified_at' => now(),
        ])->save();

        $this->info(($user->wasRecentlyCreated ? 'Created' : 'Updated')." {$role} account: {$email}");

        return self::SUCCESS;
    }
}
