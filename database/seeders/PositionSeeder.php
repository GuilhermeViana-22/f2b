<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Position;

class PositionSeeder extends Seeder
{
    public function run()
    {
        $positions = [
            ['Administrative Assistant', 1, 'Administrative', 'Responsible for general administrative tasks.'],
            ['General Director', 2, 'Administrative', 'Responsible for the overall management of the system.'],
            ['General User', 3, 'Users', 'Users are the clients off the system.'],
        ];

        // Bulk insert
        foreach ($positions as $position) {
            Position::create([
                'position' => $position[0],
                'level_hierarchical' => $position[1],
                'department' => $position[2],
                'description' => $position[3] ?? null,
            ]);
        }
    }
}
