<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'manage_organizations',
            'manage_teachers',
            'manage_students',
            'manage_courses',
            'manage_lessons',
            'manage_exams',
            'manage_wallets',
            'manage_payments',
            'view_reports',
        ];

        foreach ($permissions as $permission) {
            Permission::query()->firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        $roles = [
            'super_admin' => $permissions,
            'center_owner' => ['manage_teachers', 'manage_students', 'manage_courses', 'manage_lessons', 'manage_exams', 'manage_wallets', 'manage_payments', 'view_reports'],
            'center_admin' => ['manage_teachers', 'manage_students', 'manage_courses', 'manage_lessons', 'manage_exams', 'view_reports'],
            'teacher' => ['manage_courses', 'manage_lessons', 'manage_exams', 'view_reports'],
            'teacher_assistant' => ['manage_lessons', 'manage_students'],
            'cashier' => ['manage_payments'],
            'student' => [],
        ];

        foreach ($roles as $roleName => $rolePermissions) {
            $role = Role::query()->firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
            $role->syncPermissions($rolePermissions);
        }

        $admin = User::query()->firstOrCreate(
            ['email' => 'admin@alsakhra.local'],
            [
                'name' => 'Super Admin',
                'phone' => '01000000000',
                'password' => Hash::make('password'),
                'role' => 'super_admin',
                'status' => 'active',
            ]
        );

        $admin->assignRole('super_admin');
    }
}
