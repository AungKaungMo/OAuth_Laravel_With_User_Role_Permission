<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class SuperadminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::create([
            'name' => 'Admin',
            'email' => 'developer@gmail.com',
            'phone' => '09122345678',
            'password' => bcrypt('adminpass'),
            'type' => 'ADMIN'
        ]);

        $role = Role::create([
            'name' => 'developer',
            'guard_name' => 'api'
        ]);

        $permissions = Permission::pluck('id', 'id')->all();

        $role->syncPermissions($permissions);

        $user->assignRole('developer');
    }
}
