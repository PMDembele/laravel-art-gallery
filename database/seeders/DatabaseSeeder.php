<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Сначала картины, затем пользователи: UserSeeder назначает владельцев картинам без user_id
        $this->call([
            PaintingSeeder::class,
            UserSeeder::class,
        ]);
    }
}
