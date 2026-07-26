<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class RoleAndPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        Permission::firstOrCreate(['name' => 'view dashboard', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'manage users', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'manage roles', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'manage permissions', 'guard_name' => 'web']);

        // Kelas
        Permission::firstOrCreate(['name' => 'manage_kelas', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'view_kelas', 'guard_name' => 'web']);

        // Siswa (Granular)
        Permission::firstOrCreate(['name' => 'view_siswa', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'create_siswa', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'edit_siswa', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'delete_siswa', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'import_siswa', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'export_siswa', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'view_own_siswa_data', 'guard_name' => 'web']);

        // Tagihan SPP
        Permission::firstOrCreate(['name' => 'manage_all_tagihan', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'view_own_tagihan', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'pay_tagihan', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'manage application settings', 'guard_name' => 'web']);
        
        // --- Buat Roles dan berikan permissions ---
        // Role Admin
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $adminRole->givePermissionTo(Permission::all()); 

        $adminKelasRole = Role::firstOrCreate(['name' => 'admin_kelas', 'guard_name' => 'web']);
        $adminKelasRole->givePermissionTo([
            'view_siswa', 
            'create_siswa', 
            'edit_siswa', 
            'delete_siswa', 
            'import_siswa', 
            'export_siswa'
        ]);
        $adminKelasRole->givePermissionTo('manage_all_tagihan');

        // User
        $userRole = Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
        $userRole->givePermissionTo([
            'view_kelas',
            'view dashboard',
        ]);

        // Siswa
        $siswaRole = Role::firstOrCreate(['name' => 'siswa', 'guard_name' => 'web']);
        $siswaRole->givePermissionTo([
            'view_own_siswa_data',
            'view_own_tagihan',
            'pay_tagihan',
        ]);

        $this->command->info('Roles and Permissions seeded successfully!');
    }
}
