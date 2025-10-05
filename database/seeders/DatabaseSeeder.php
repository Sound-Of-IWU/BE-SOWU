<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Pengurus;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Seeder default pengurus (admin)
        $user = User::create([
            'nama_lengkap' => 'Administrator Utama',
            'nim' => '00000001',
            'gender' => 'male',
            'phone_wa' => '081234567890',
            'username_instagram' => null,
            'fakultas' => 'Fakultas Musik',
            'prodi' => 'Vokal',
            'angkatan' => '2025',
            'alasan' => 'Seeder default pengurus.',
            'range_suara' => 'belum_ada',
            'role' => 'pengurus',
            'status' => 'aktif',
            'email' => 'admin@example.com',
            'password' => 'password123', // akan otomatis di-hash oleh cast
        ]);

        Pengurus::create([
            'user_id' => $user->id,
            'username' => 'soundofiwu',
            'password' => 'wOzp4a8z0tKjIl', // di-hash oleh cast di model Pengurus
            'divisi' => 'manajemen',
        ]);

        $this->command?->info('Pengurus default berhasil dibuat: username=soundofiwu / password=wOzp4a8z0tKjIl');
    }
}
