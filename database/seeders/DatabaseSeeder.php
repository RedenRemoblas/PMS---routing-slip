<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Define roles
        $roles = [
            'user',
            'admin',
            'leave-admin',
            'dtr-admin',
            'to-admin',
            'hr-admin',
        ];

        // Create roles if they don't exist
        foreach ($roles as $role) {
            Role::firstOrCreate(['name' => $role]);
        }

        // Create or update admin user
        $adminUser = User::updateOrCreate(
            [
                'email' => 'allan.lao@dict.gov.ph',
            ],
            [
                'name' => 'Allan Lao',
                'google_id' => '115098783060946018090',
                'password' => Hash::make('secret'),
                'is_active' => 1,
            ]
        );

        $adminUser->assignRole('admin');

        // ✅ Call additional seeders
        $this->call([
            LeaveTypesTableSeeder::class,
            PlacesTableSeeder::class,
        ]);
    }
}
