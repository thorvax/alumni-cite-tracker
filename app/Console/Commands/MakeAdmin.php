<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class MakeAdmin extends Command
{
    protected $signature = 'cite:make-admin {email} {--name=Admin} {--position=Administrator}';

    public function handle(): int
    {
        $password = (string) $this->secret('Password (at least 8 characters)');

        if (strlen($password) < 8) {
            $this->error('The password must be at least 8 characters.');

            return self::FAILURE;
        }

        $user = User::query()->firstOrNew(['email' => (string) $this->argument('email')]);

        $user->forceFill([
            'name' => (string) $this->option('name'),
            'position' => (string) $this->option('position'),
            'password' => Hash::make($password),
            'email_verified_at' => now(),
        ])->save();

        $this->info("Admin account ready: {$user->email}");

        return self::SUCCESS;
    }
}
