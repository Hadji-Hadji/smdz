<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Package extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'price',
        'duration_in_days',
        'features', // حقل JSON
        'is_active',
    ];

    protected $casts = [
        'features' => 'array',      // تحويل JSON إلى مصفوفة تلقائياً عند التعامل مع الموديل
        'is_active' => 'boolean',
        'price' => 'decimal:2',     // ضمان بقاء السعر بصيغة عشرية دقيقة
    ];

    /**
     * الحصول على قيمة ميزة معينة من الـ JSON بسهولة.
     * مثال: $package->getFeature('max_listings', 0);
     */
    public function getFeature($key, $default = null)
    {
        // التحقق من أن features ليست فارغة وهي مصفوفة
        if (is_array($this->features)) {
            return $this->features[$key] ?? $default;
        }
        
        return $default;
    }

    /**
     * التحقق من وجود ميزة (Boolean) وتفعيلها.
     * مثال: if($package->hasFeature('featured_ads')) { ... }
     */
    public function hasFeature($key)
    {
        return isset($this->features[$key]) && 
               ($this->features[$key] === true || $this->features[$key] === 'true' || $this->features[$key] === 1);
    }

    // العلاقات (Relationships)
    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }
}