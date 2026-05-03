<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureLandlordVerified
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        // التحقق من نوع المستخدم
        if (!$user || $user->type !== 'landlord') {
            return response()->json([
                'status' => 'error',
                'message' => 'الملاك فقط هم من يمكنهم القيام بهذه العملية'
            ], 403);
        }

        // التحقق من حالة التوثيق باستخدام الحقل الجديد verification_status
        // الحالة المسموح لها بإضافة العقارات هي 'verified' فقط
        if ($user->verification_status !== 'verified') {
            $message = 'لا يمكنك إضافة أو تعديل شقة قبل التحقق من حسابك.';
            
            if ($user->verification_status === 'pending') {
                $message = 'حسابك قيد المراجعة حالياً، يرجى الانتظار حتى يتم التوثيق.';
            } elseif ($user->verification_status === 'rejected') {
                $message = 'تم رفض وثائق التوثيق الخاصة بك، يرجى إعادة الرفع مرة أخرى.';
            }

            return response()->json([
                'status' => 'error',
                'message' => $message,
                'current_status' => $user->verification_status
            ], 403);
        }

        return $next($request);
    }
}