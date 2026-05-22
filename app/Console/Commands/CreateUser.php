<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class CreateUser extends Command
{
    protected $signature = 'app:create-user';
    protected $description = 'Create an admin user';

    public function handle(): void
    {
        $firstname = $this->ask('First name');
        $name = $this->ask('Last name');
        $email = $this->ask('Email');
        $password = $this->secret('Password');

        if (User::where('email', $email)->exists()) {
            $this->error("A user with email {$email} already exists.");
            return;
        }

        User::create([
            'firstname' => $firstname,
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'role' => 'admin',
        ]);

        $this->info("Admin user {$firstname} {$name} ({$email}) created.");
    }
}
