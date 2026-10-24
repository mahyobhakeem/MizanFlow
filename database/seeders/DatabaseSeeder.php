<?php

namespace Database\Seeders;

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
        // 1. المدير العام (Administrator)
        User::updateOrCreate(
            ['username' => 'admin'],
            [
                'name' => 'المدير العام',
                'email' => 'admin@mizanflow.test',
                'password' => Hash::make('password123'),
                'role' => 'admin',
                'is_active' => true,
            ]
        );

        // 2. مدير العمليات والمستودعات (Operations Manager)
        User::updateOrCreate(
            ['username' => 'manager'],
            [
                'name' => 'مدير العمليات والمستودعات',
                'email' => 'manager@mizanflow.test',
                'password' => Hash::make('password123'),
                'role' => 'manager',
                'is_active' => true,
            ]
        );

        // 3. أمين الصندوق / الكاشير (Cashier)
        User::updateOrCreate(
            ['username' => 'cashier'],
            [
                'name' => 'أمين الصندوق (كاشير)',
                'email' => 'cashier@mizanflow.test',
                'password' => Hash::make('password123'),
                'role' => 'cashier',
                'is_active' => true,
            ]
        );

        // 4. البيانات التجريبية والأساسية (Master & Demo Data)
        $this->call(MasterDataSeeder::class);
    }
}
