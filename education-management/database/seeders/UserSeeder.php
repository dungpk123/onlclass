<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $adminRole = Role::where('name', 'admin')->first();
        $teacherRole = Role::where('name', 'teacher')->first();
        $studentRole = Role::where('name', 'student')->first();

        // Admin user
        User::create([
            'name' => 'Admin User',
            'email' => 'admin@education.com',
            'password' => Hash::make('password'),
            'role_id' => $adminRole->id,
            'phone' => '0123456789'
        ]);

        // Teacher users
        User::create([
            'name' => 'Nguyễn Văn Giảng',
            'email' => 'teacher1@education.com',
            'password' => Hash::make('password'),
            'role_id' => $teacherRole->id,
            'phone' => '0123456780'
        ]);

        User::create([
            'name' => 'Trần Thị Minh',
            'email' => 'teacher2@education.com',
            'password' => Hash::make('password'),
            'role_id' => $teacherRole->id,
            'phone' => '0123456781'
        ]);

        // Student users
        User::create([
            'name' => 'Lê Văn Học',
            'email' => 'student1@education.com',
            'password' => Hash::make('password'),
            'role_id' => $studentRole->id,
            'phone' => '0123456782'
        ]);

        User::create([
            'name' => 'Pham Thị Sinh',
            'email' => 'student2@education.com',
            'password' => Hash::make('password'),
            'role_id' => $studentRole->id,
            'phone' => '0123456783'
        ]);
    }
}
