<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Slot;

class SlotSeeder extends Seeder
{
    public function run(): void
    {
        Slot::query()->delete();

        Slot::create(['name' => 'Slot A', 'capacity' => 10, 'remaining' => 10]);
        Slot::create(['name' => 'Slot B', 'capacity' => 5, 'remaining' => 0]);
    }
}
