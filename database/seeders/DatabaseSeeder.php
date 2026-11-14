<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Mei Admin',
            'email' => 'admin@meilibrary.local',
            'password' => 'password',
        ]);

        foreach ([
            ['Laut Bercerita', 'Fiksi', 5], ['Bumi Manusia', 'Fiksi', 3],
            ['Atomic Habits', 'Pengembangan Diri', 6], ['Filosofi Teras', 'Pengembangan Diri', 2],
            ['Sapiens', 'Sejarah', 4], ['Sejarah Dunia', 'Sejarah', 3],
            ['Laskar Pelangi', 'Fiksi', 7], ['The Psychology of Money', 'Bisnis', 4],
        ] as [$title, $genre, $stock]) {
            \App\Models\Book::create(compact('title', 'genre', 'stock'));
        }
    }
}
