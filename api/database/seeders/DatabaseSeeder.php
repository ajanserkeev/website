<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Local and demo environments only: production data is entered in the admin.
     * Model events stay on: medialibrary sets each photo's uuid and order in them.
     */
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'role' => UserRole::Admin,
        ]);

        $this->call(DemoCatalogSeeder::class);
    }
}
