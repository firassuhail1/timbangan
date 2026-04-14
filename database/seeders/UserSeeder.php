<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $data = [
            [
                'id' => 1,
                'username' => 'admin',
                'line' => null,
                'password' => bcrypt('1'),
                'role' => 'admin',
                'status' => 'Aktif',
            ],
            [
                'id' => 2,
                'username' => 'Wahyu',
                'line' => 'LINE001',
                'password' => bcrypt('1'),
                'role' => 'user',
                'status' => 'Aktif',
            ],
            [
                'id' => 3,
                'username' => 'Bayu',
                'line' => 'LINE002',
                'password' => bcrypt('1'),
                'role' => 'user',
                'status' => 'Aktif',
            ],
            [
                'id' => 4,
                'username' => 'Dani',
                'line' => 'LINE003',
                'password' => bcrypt('1'),
                'role' => 'user',
                'status' => 'Aktif',
            ],
            [
                'id' => 5,
                'username' => 'Denis',
                'line' => 'LINE004',
                'password' => bcrypt('1'),
                'role' => 'user',
                'status' => 'Aktif',
            ],
        ];

        foreach ($data as $key => $value) {
            User::create($value);
        }
    }
}
