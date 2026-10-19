<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Pengurus;
use Illuminate\Database\Seeder;

class PengurusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Buat atau perbarui data User pengurus
        $user = User::updateOrCreate(
            ['nim' => 'ADMIN001'],
            [
                'nama_lengkap' => 'Admin SOWU',
                'gender' => 'male',
                'phone_wa' => '081234567890',
                'username_instagram' => 'admin_sowu',
                'fakultas' => 'Fak. Sains & Teknologi',
                'prodi' => 'Informatics',
                'angkatan' => '2025',
                'alasan' => 'Akun Administrator Pengurus SOWU',
                'range_suara' => 'tenor',
                'role' => 'pengurus',
                'status' => 'aktif',
            ]
        );

        // 2. Buat atau perbarui kredensial Pengurus (username: admin, password: admin_admin)
        Pengurus::updateOrCreate(
            ['user_id' => $user->id],
            [
                'username' => 'admin',
                'password' => 'admin_admin', // Otomatis di-hash oleh casts di model Pengurus
                'divisi' => 'Ketua',
            ]
        );

        $this->command?->info('Pengurus seeder berhasil dijalankan: username=admin, password=admin_admin');
    }
}
