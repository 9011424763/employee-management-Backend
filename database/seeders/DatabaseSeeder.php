<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        Department::query()->updateOrCreate(
            ['code' => 'HR'],
            ['name' => 'HR', 'description' => 'Human resources']
        );
        $it = Department::query()->updateOrCreate(
            ['code' => 'IT'],
            ['name' => 'IT', 'description' => 'Information technology']
        );
        Department::query()->updateOrCreate(
            ['code' => 'SLS'],
            ['name' => 'Sales', 'description' => 'Sales and marketing']
        );

        User::query()->updateOrCreate(
            ['email' => 'admin@ems.test'],
            [
                'name' => 'System Admin',
                'password' => Hash::make('password'),
                'role' => 'admin',
            ],
        );

        User::query()->updateOrCreate(
            ['email' => 'jane@ems.test'],
            [
                'name' => 'Jane Employee',
                'password' => Hash::make('password'),
                'phone' => '555-0100',
                'department_id' => $it->id,
                'job_title' => 'Software Developer',
                'role' => 'employee',
                'salary' => 75000,
                'joining_date' => '2023-01-15',
            ],
        );
    }
}
