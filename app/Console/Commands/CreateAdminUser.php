<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

class CreateAdminUser extends Command
{
    protected $signature = 'users:create-admin';

    protected $description = 'Create an administrator account (use this for the first login on a new installation)';

    public function handle(): int
    {
        $data = [
            'name' => text('Name', required: true),
            'email' => text('Email address', required: true),
            'password' => password('Password', required: true, hint: 'At least 10 characters, mixed case and a number.'),
        ];

        $validator = Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', Password::defaults()],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->components->error($error);
            }

            return self::FAILURE;
        }

        $user = new User($validator->validated());
        $user->role = UserRole::Admin;
        $user->email_verified_at = now();
        $user->save();

        $this->components->info("Administrator {$user->email} created.");

        return self::SUCCESS;
    }
}
