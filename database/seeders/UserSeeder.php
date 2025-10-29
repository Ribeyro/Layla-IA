<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $users = [
            [
                'name' => 'Ribeyro Quispe',
                'email' => 'ribeyro.quispe@layla.ia.com',
                'password' => Hash::make('123456'),
                'email_verified_at' => now(),
            ],
            [
                'name' => 'Santiago Pisconte',
                'email' => 'santiago.pisconte@layla.ia.com',
                'password' => Hash::make('123456'),
                'email_verified_at' => now(),
            ],
            [
                'name' => 'Linda Aroni',
                'email' => 'linda.aroni@layla.ia.com',
                'password' => Hash::make('123456'),
                'email_verified_at' => now(),
            ],
            [
                'name' => 'Patrick Ayllon',
                'email' => 'patrick.ayllon@layla.ia.com',
                'password' => Hash::make('123456'),
                'email_verified_at' => now(),
            ],
        ];

        foreach ($users as $user) {
            User::create($user);
        }
    }
}
