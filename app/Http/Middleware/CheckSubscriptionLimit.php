<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Apartment;

class CheckSubscriptionLimit
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse) $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        // 1. التأكد من أن المستخدم مسجل دخول ومن نوع مالك (Landlord)
        if (!$user || $user->type !== 'landlord') {
            return response()->json([
                'status' => 'error',
                'message' => 'هذا الإجراء مخصص للملاك فقط.'
            ], 403);
        }

        // 2. استخدام العلاقة التي أنشأناها في موديل User لجلب الاشتراك النشط مع الباقة
        $activeSubscription = $user->activeSubscription()->with('package')->first();

        if (!$activeSubscription) {
            return response()->json([
                'status' => 'error',
                'message' => 'ليس لديك اشتراك نشط. يرجى الاشتراك في باقة لتتمكن من إضافة شقق.',
                'action' => 'subscribe_required'
            ], 403);
        }

        // 3. التحقق من حد الشقق من داخل حقل features JSON باستخدام الدالة المساعدة getFeature
        $package = $activeSubscription->package;
        
        // استدعاء max_listings باستخدام الدالة التي أضفناها في موديل Package
        $maxListings = $package->getFeature('max_listings');

        // 4. إذا كان هناك حد محدد (ليس null)، نقوم بالتحقق من العدد الحالي
        if ($maxListings !== null) {
            $currentApartmentsCount = Apartment::where('landlord_id', $user->id)->count();

            if ($currentApartmentsCount >= (int) $maxListings) {
                return response()->json([
                    'status' => 'error',
                    'message' => "لقد وصلت إلى الحد الأقصى المسموح به من الشقق في باقتك الحالية ({$maxListings} شقة). يرجى ترقية باقتك لإضافة المزيد.",
                    'limit' => (int) $maxListings,
                    'current_count' => $currentApartmentsCount,
                    'action' => 'upgrade_required'
                ], 403);
            }
        }

        // إذا اجتاز كل الفحوصات، اسمح للطلب بالمرور إلى الـ Controller
        return $next($request);
    }
}