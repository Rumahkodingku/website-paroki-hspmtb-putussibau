<?php

namespace Database\Seeders\Demo;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Creates the shared demo author used by news and agenda seeders.
 *
 * Idempotent: the author row is keyed by email.
 */
class DemoAuthorSeeder extends Seeder
{
    public function run(): void
    {
        $author = User::query()->updateOrCreate(
            ['email' => 'redaksi-demo@example.test'],
            [
                'name' => 'Redaksi Contoh',
                'password' => Hash::make(Str::random(40)),
            ],
        );

        $author->forceFill([
            'is_active' => true,
            'email_verified_at' => now(),
        ])->save();
    }
}
