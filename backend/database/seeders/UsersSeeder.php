<?php

namespace Database\Seeders;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $organizations = Organization::query()->get();

        foreach ($organizations as $organization) {
            User::updateOrCreate(
                ['email' => "org{$organization->id}.admin@example.com"],
                [
                    'name' => "Org {$organization->id} Admin",
                    'password' => Hash::make('password'),
                    'role' => 'org_admin',
                    'organization_id' => $organization->id,
                    'email_verified_at' => now(),
                ]
            );

            User::updateOrCreate(
                ['email' => "org{$organization->id}.user@example.com"],
                [
                    'name' => "Org {$organization->id} User",
                    'password' => Hash::make('password'),
                    'role' => 'user',
                    'organization_id' => $organization->id,
                    'email_verified_at' => now(),
                ]
            );
        }
    }
}
