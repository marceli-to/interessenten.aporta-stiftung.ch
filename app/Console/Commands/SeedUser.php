<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class SeedUser extends Command
{
    protected $signature = 'app:seed-user';
    protected $description = 'Seed the admin user';

    public function handle(): void
    {
        User::firstOrCreate(
            ['email' => 'm@marceli.to'],
            [
                'firstname' => 'Marcel',
                'name' => 'Stadelmann',
                'password' => Hash::make('7aq31rr23'),
                'role' => 'admin',
            ]
        );

        $this->info('Admin user seeded.');
    }
}
