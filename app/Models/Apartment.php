<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Apartment extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        "landlord_id",
        "title",
        "description",
        "wilaya",
        "municipality",
        "price",
        "price_unit",
        "rooms",
        "bathrooms",
        "area",
        "is_active",
        "is_featured",
        "status",
        "admin_notes",
        "views_count",
        "phone_clicks",
        "amenities",
        "images",
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        "is_active" => "boolean",
        "is_featured" => "boolean",
        "amenities" => "array", // Cast JSON column to array
        "images" => "array",    // Cast JSON column to array
        "price" => "decimal:2",
        "area" => "decimal:2",
    ];

    /**
     * Accessor: يقوم بتحويل مسارات الصور إلى روابط كاملة تلقائياً
     * سيعمل هذا التعديل فوراً في دالة index و show و store
     */
    public function getImagesAttribute($value)
    {
        if (!$value) return [];

        // تحويل القيمة لمصفوفة إذا كانت مخزنة كـ JSON string
        $images = is_array($value) ? $value : json_decode($value, true);

        return array_map(function ($image) {
            // إذا كان الرابط خارجياً (مثل Faker) يبدأ بـ http، نتركه كما هو
            if (str_starts_with($image, 'http')) {
                return $image;
            }
            
            // تحويل المسار النسبي إلى رابط URL كامل
            return asset('storage/' . $image);
        }, $images);
    }

    // Define relationships
    public function landlord()
    {
        return $this->belongsTo(User::class, "landlord_id");
    }

    public function favoritedBy()
    {
        return $this->hasMany(Favorite::class);
    }
}