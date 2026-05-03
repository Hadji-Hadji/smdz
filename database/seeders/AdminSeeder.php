<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Admin;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        // مصفوفة تحتوي على بيانات المديرين بكلمات مرور مخصصة وفريدة
        $adminUsers = [
            [
                'name' => 'Super Admin',
                'email' => 'superadmin@smartrent.dz',
                'password' => 'Admin@SR2026!', // كلمة مرور قوية وفريدة
                'role' => 'super_admin',
                'permissions' => ['manage_users', 'manage_packages', 'manage_payments', 'manage_apartments'],
                'phone' => '0550000001'
            ],
            [
                'name' => 'Finance Admin',
                'email' => 'finance@smartrent.dz',
                'password' => 'Fin#Smart!99', 
                'role' => 'finance_admin',
                'permissions' => ['manage_payments', 'manage_packages'],
                'phone' => '0550000002'
            ],
            [
                'name' => 'Support Admin',
                'email' => 'support@smartrent.dz',
                'password' => 'Supp_Rent_2026',
                'role' => 'support_admin',
                'permissions' => ['manage_apartments', 'manage_users'],
                'phone' => '0550000003'
            ],
            [
                'name' => 'Verification Admin',
                'email' => 'verification@smartrent.dz',
                'password' => 'Verify@Safe_DZ',
                'role' => 'verification_admin',
                'permissions' => ['manage_users'],
                'phone' => '0550000004'
            ],
        ];

        foreach ($adminUsers as $adminData) {
            // 1. إنشاء أو جلب المستخدم في جدول users
            $user = User::firstOrCreate(
                ['email' => $adminData['email']],
                [
                    'name'                => $adminData['name'],
                    'password'            => Hash::make($adminData['password']), // تشفير كلمة المرور الخاصة بكل مسؤول
                    'phone'               => $adminData['phone'],
                    'type'                => 'renter', 
                    'verification_status' => 'verified',
                    'email_verified_at'   => now(),
                ]
            );

            // 2. ربط المستخدم بجدول admins وتحديد الدور والصلاحيات
            Admin::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'role'        => $adminData['role'],
                    'permissions' => json_encode($adminData['permissions'])
                ]
            );
        }

        $this->command->info('تم إنشاء حسابات المديرين بكلمات مرور مخصصة وآمنة.');
    }
}