<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Laravel\Sanctum\NewAccessToken;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        /** @var User $user */
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
        ]);

        $newAccessToken = $user->createToken('test-token');
        assert($newAccessToken instanceof NewAccessToken);
        $plainTextToken = $newAccessToken->plainTextToken;

        $this->command->info("User created with email: test@example.com");
        $this->command->info("API Token: {$plainTextToken}");
    }
}
