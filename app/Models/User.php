<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'type', // landlord, renter, admin
        'uid',
        'verification_status', 
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     */
    public function getJWTCustomClaims()
    {
        return [];
    }

    // --- العلاقات البرمجية (Relationships) ---

    /**
     * علاقة الحصول على الاشتراك النشط الحالي فقط.
     * تتحقق من أن الاشتراك مفعل (is_active) وتاريخ نهايته لم يأتِ بعد.
     */
    public function activeSubscription()
    {
        return $this->hasOne(Subscription::class)
                    ->where('is_active', true)
                    ->where('end_date', '>', now())
                    ->latest('end_date'); // في حال وجود اشتراكين متداخلين بالخطأ، نأخذ الأحدث
    }

    public function apartments()
    {
        return $this->hasMany(Apartment::class, 'landlord_id');
    }

    public function favorites()
    {
        return $this->hasMany(Favorite::class);
    }

    public function verificationDocuments()
    {
        return $this->hasMany(VerificationDocument::class, 'user_id');
    }

    public function admin()
    {
        return $this->hasOne(Admin::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }
}