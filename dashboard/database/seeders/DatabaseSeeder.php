<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Admin user
        DB::table('users')->insertOrIgnore([
            'name'               => env('ADMIN_NAME', 'Super Admin'),
            'email'              => env('ADMIN_EMAIL', 'admin@oynaa.uz'),
            'phone'              => '+998901234567',
            'password'           => Hash::make(env('ADMIN_PASSWORD', 'Admin@123')),
            'role'               => 'admin',
            'is_active'          => true,
            'is_verified'        => true,
            'phone_verified_at'  => now(),
            'created_at'         => now(),
            'updated_at'         => now(),
        ]);

        // Categories
        $categories = [
            ['name_uz' => 'Futbol', 'name_ru' => 'Футбол', 'icon' => null, 'sort_order' => 1],
            ['name_uz' => 'Mini futbol', 'name_ru' => 'Мини футбол', 'icon' => null, 'sort_order' => 2],
            ['name_uz' => 'Basketbol', 'name_ru' => 'Баскетбол', 'icon' => null, 'sort_order' => 3],
            ['name_uz' => 'Tennis', 'name_ru' => 'Теннис', 'icon' => null, 'sort_order' => 4],
            ['name_uz' => 'Voleybol', 'name_ru' => 'Волейбол', 'icon' => null, 'sort_order' => 5],
            ['name_uz' => 'Badminton', 'name_ru' => 'Бадминтон', 'icon' => null, 'sort_order' => 6],
            ['name_uz' => 'Padel', 'name_ru' => 'Падел', 'icon' => null, 'sort_order' => 7],
            ['name_uz' => 'Xokkey', 'name_ru' => 'Хоккей', 'icon' => null, 'sort_order' => 8],
        ];

        foreach ($categories as $cat) {
            DB::table('categories')->insertOrIgnore(array_merge($cat, [
                'is_active'  => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }

        $this->command->info('✅ Admin va kategoriyalar yaratildi!');
        $this->command->info('📧 Email: ' . env('ADMIN_EMAIL', 'admin@oynaa.uz'));
        $this->command->info('🔑 Parol: ' . env('ADMIN_PASSWORD', 'Admin@123'));
    }
}
