<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class CreateSuperAdmin extends Command
{
    protected $signature = 'app:create-superadmin {name} {email} {password}';
    protected $description = 'Create a platform superadmin account (one-time bootstrap, no public signup exists for this role)';

    public function handle(): int
    {
        $email = $this->argument('email');

        if (User::where('email', $email)->exists()) {
            $this->error("A user with email {$email} already exists.");
            return self::FAILURE;
        }

        $user = User::create([
            'name' => $this->argument('name'),
            'email' => $email,
            'password' => $this->argument('password'),
            'restaurant_id' => null,
        ]);

        $user->assignRole('superadmin');

        $this->info("Superadmin created: {$user->email}");
        return self::SUCCESS;
    }
}