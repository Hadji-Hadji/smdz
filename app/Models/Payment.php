<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'package_id',
        'amount',
        'payment_method',
        'status',
        'receipt_image_path',
        'admin_notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    /**
     * Accessor: تحويل مسار وصل الدفع إلى رابط كامل
     */
    public function getReceiptImagePathAttribute($value)
    {
        if (!$value) {
            return null;
        }

        // إذا كان الرابط يبدأ بـ http (روابط تجريبية أو خارجية)
        if (str_starts_with($value, 'http')) {
            return $value;
        }

        // تحويل المسار المخزن في storage إلى رابط URL كامل
        return asset('storage/' . $value);
    }

    // --- العلاقات ---

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function package()
    {
        return $this->belongsTo(Package::class);
    }
}