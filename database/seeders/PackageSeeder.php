<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Package;

class PackageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $packages = [
            [
                "name" => "المجانية",
                "description" => "باقة ترحيبية لتجربة المنصة ونشر إعلانك الأول",
                "price" => 0.00,
                "duration_in_days" => 15,
                "is_active" => true,
                "features" => [
                    "max_listings" => 1,
                    "listing_duration" => "15 days",
                    "visibility_rank" => "low",
                    "max_images" => 5,
                    "analytics" => "views_only",
                    "featured_ads" => false
                ]
            ],
            [
                "name" => "Basic",
                "description" => "الخيار الأمثل للملاك الصغار بمدى ظهور عادي",
                "price" => 1800.00,
                "duration_in_days" => 30,
                "is_active" => true,
                "features" => [
                    "max_listings" => 3,
                    "listing_duration" => "30 days",
                    "visibility_rank" => "normal",
                    "max_images" => 10,
                    "analytics" => "views_and_clicks",
                    "featured_ads" => false
                ]
            ],
            [
                "name" => "Pro",
                "description" => "للمستثمرين العقاريين مع أولوية في الظهور وإحصائيات متقدمة",
                "price" => 3900.00,
                "duration_in_days" => 30,
                "is_active" => true,
                "features" => [
                    "max_listings" => 10,
                    "listing_duration" => "30 days",
                    "visibility_rank" => "priority",
                    "max_images" => 20,
                    "analytics" => "advanced",
                    "featured_ads" => true
                ]
            ]
        ];

        foreach ($packages as $packageData) {
            // نستخدم updateOrCreate لتجنب تكرار البيانات إذا قمت بتشغيل الأمر أكثر من مرة
            Package::updateOrCreate(
                ['name' => $packageData['name']], 
                $packageData
            );
        }
    }
}