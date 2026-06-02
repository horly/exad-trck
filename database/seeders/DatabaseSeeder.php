<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Fleet;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $adminSubscription = Subscription::query()->updateOrCreate(
            ['slug' => 'admin-subscription'],
            [
                'name' => 'Abonnement Admin EXAD',
                'status' => 'active',
                'starts_at' => now(),
                'ends_at' => null,
                'metadata' => [],
            ],
        );

        User::query()->updateOrCreate(
            ['email' => 'superadmin@erp.loc'],
            [
                'subscription_id' => null,
                'name' => 'superadmin',
                'password' => Hash::make('H@mshyef@#154dsgfd'),
                'role' => UserRole::Superadmin,
                'status' => 'active',
                'disabled_at' => null,
                'permissions' => [],
            ],
        );

        User::query()->updateOrCreate(
            ['email' => 'admin@erp.loc'],
            [
                'subscription_id' => $adminSubscription->id,
                'name' => 'admin',
                'password' => Hash::make('ATRbhgdfbgf@#154dsgfd'),
                'role' => UserRole::Admin,
                'status' => 'active',
                'disabled_at' => null,
                'permissions' => [],
            ],
        );

        User::query()->updateOrCreate(
            ['email' => 'user1@erp.loc'],
            [
                'subscription_id' => $adminSubscription->id,
                'name' => 'user1',
                'password' => Hash::make('tYhdhsfe154@sh#sgfd'),
                'role' => UserRole::User,
                'status' => 'active',
                'disabled_at' => null,
                'permissions' => [],
            ],
        );

        Fleet::query()->updateOrCreate(
            [
                'subscription_id' => $adminSubscription->id,
                'code' => 'FLT-ADMIN',
            ],
            [
                'name' => 'Flotte principale Admin',
                'description' => 'Flotte rattachee a l abonnement de admin.',
                'status' => 'active',
            ],
        );
    }
}
