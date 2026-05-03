<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VerificationDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'document_type',
        'document_path',
        'status'
    ];

    /**
     * Accessor: تحويل مسار وثيقة التحقق إلى رابط URL كامل
     */
    public function getDocumentPathAttribute($value)
    {
        if (!$value) {
            return null;
        }

        // التعامل مع الروابط الخارجية (مثل Faker)
        if (str_starts_with($value, 'http')) {
            return $value;
        }

        // تحويل المسار النسبي إلى رابط كامل
        return asset('storage/' . $value);
    }

    // --- العلاقات ---

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}