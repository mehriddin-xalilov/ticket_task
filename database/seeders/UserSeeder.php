<?php

namespace Database\Seeders;

use App\Helpers\Roles;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */

    public function run(): void
    {
        $roles = Roles::asArray();

        foreach ($roles as $index => $role) {
            $user = User::create([
                'name' => ucfirst(str_replace('_', ' ', $role)),
                'login' => $role,
                'email' => $role . '@example.com',
                'password' => Hash::make($role),
                'status' => 1,
                'phone' => '+99894' . str_pad(920000 + $index, 4, '0', STR_PAD_LEFT),
            ]);
            UserRole::create([
                'name' => $role,
                'role' => $role,
                'user_id' => $user->id,
            ]);
        }
    }

}
