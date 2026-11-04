<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Support\Permissions;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

/**
 * Creates (or promotes) a staff account interactively, so no password is ever
 * written into code, seeders, shell history or chat.
 */
class CreateAdmin extends Command
{
    protected $signature = 'alraed:create-user {--role=admin : admin|supervisor|employee|cashier|viewer}';

    protected $description = 'Create a staff account with a role (password is typed hidden)';

    public function handle(): int
    {
        $role = (string) $this->option('role');
        if (! array_key_exists($role, Permissions::roles())) {
            $this->error('Unknown role. Use one of: ' . implode(', ', array_keys(Permissions::roles())));

            return self::FAILURE;
        }

        $this->callSilently('db:seed', ['--class' => RolesAndPermissionsSeeder::class, '--force' => true]);

        $name = $this->ask('Full name');
        $email = mb_strtolower(trim((string) $this->ask('Email')));
        $password = $this->secret('Password (min 12 chars, hidden)');
        $confirm = $this->secret('Repeat password');

        $validator = Validator::make(
            ['name' => $name, 'email' => $email, 'password' => $password, 'password_confirmation' => $confirm],
            [
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'email', 'max:255'],
                'password' => ['required', 'confirmed', Password::min(12)->letters()->numbers()],
            ],
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $message) {
                $this->error($message);
            }

            return self::FAILURE;
        }

        $user = User::query()->firstOrNew(['email' => $email]);
        $existed = $user->exists;
        $user->forceFill(['name' => $name, 'password' => $password, 'is_active' => true])->save();
        $user->syncRoles([$role]);

        $this->info(($existed ? 'Updated' : 'Created') . " {$email} with role [{$role}].");

        return self::SUCCESS;
    }
}
