<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Apartment;
use App\Models\VerificationDocument;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class LandlordController extends Controller
{
    /**
     * جلب جميع الشقق الخاصة بالمالك الحالي
     */
    public function landlordApartments()
    {
        $user = auth('api')->user();

        if (!$user) {
            return response()->json([
                "status" => "error",
                "message" => "غير مصرح بالدخول"
            ], 401);
        }

        if ($user->type !== 'landlord') {
            return response()->json([
                "status" => "error",
                "message" => "فقط الملاك يمكنهم الوصول لهذه البيانات"
            ], 403);
        }

        $apartments = Apartment::where('landlord_id', $user->id)
            ->get(['id', 'title', 'wilaya', 'price', 'status', 'images']);

        return response()->json([
            "status" => "success",
            "data" => $apartments
        ]);
    }

    /**
     * رفع وثيقة التحقق من الهوية أو رخصة السياقة
     */
    public function uploadVerificationDocument(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = auth('api')->user();

        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
        }

        if ($user->type !== 'landlord') {
            return response()->json(['status' => 'error', 'message' => 'فقط الملاك يمكنهم رفع وثائق التحقق'], 403);
        }

        $request->validate([
            'document_type' => 'required|string|in:ID Card,Driving License',
            'document_file' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ], [
            'document_type.in' => 'يجب أن تكون الوثيقة إما ID Card أو Driving License فقط.',
            'document_file.required' => 'يرجى اختيار ملف الوثيقة.',
            'document_file.max' => 'حجم الملف يجب أن لا يتجاوز 5 ميجابايت.'
        ]);

        // التحقق من وجود وثيقة قيد الانتظار أو مقبولة لنفس النوع
        $existing = VerificationDocument::where('user_id', $user->id)
            ->where('document_type', $request->document_type)
            ->orderBy('created_at', 'desc')
            ->first();

        if ($existing) {
            if ($existing->status === 'pending') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'لديك وثيقة قيد الانتظار من هذا النوع بالفعل. يرجى انتظار مراجعة الأدمن.'
                ], 403);
            }

            if ($existing->status === 'approved') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'تم قبول وثيقتك من هذا النوع مسبقاً. لا حاجة لمزيد من التحقق.'
                ], 403);
            }
        }

        // حفظ الملف
        $file = $request->file('document_file');
        $path = $file->store('verification_documents', 'public');

        // إنشاء سجل الوثيقة
        $verification = VerificationDocument::create([
            'user_id' => $user->id,
            'document_type' => $request->document_type,
            'document_path' => $path,
            'status' => 'pending'
        ]);

        // تحديث حالة المستخدم الإجمالية إلى "قيد الانتظار"
        // استخدمنا الـ type hint أعلاه لتفادي خطأ Intelephense
        $user->update([
            'verification_status' => 'pending'
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'تم رفع وثيقة التحقق بنجاح. هي الآن بانتظار مراجعة الأدمن.',
            'data' => [
                'id' => $verification->id,
                'user_id' => $user->id,
                'document_type' => $verification->document_type,
                'document_path' => url('storage/' . $verification->document_path),
                'status' => $verification->status,
                'user_verification_status' => $user->verification_status
            ]
        ], 201);
    }

    /**
     * جلب إحصائيات المالك
     */
    public function stats()
    {
        $user = auth('api')->user();

        if (!$user) {
            return response()->json([
                "status" => "error",
                "message" => "Unauthorized"
            ], 401);
        }

        if ($user->type !== 'landlord') {
            return response()->json([
                "status" => "error",
                "message" => "فقط الملاك يمكنهم الوصول لهذه البيانات"
            ], 403);
        }

        $startOfMonth = Carbon::now()->startOfMonth();

        // جمع الإحصائيات بكفاءة باستخدام استعلام واحد
        $stats = Apartment::where('landlord_id', $user->id)
            ->selectRaw('
                SUM(views_count) as total_views,
                SUM(phone_clicks) as phone_clicks,
                SUM(CASE WHEN created_at >= ? THEN views_count ELSE 0 END) as monthly_views,
                SUM(CASE WHEN status = "approved" THEN 1 ELSE 0 END) as published_apartments,
                SUM(CASE WHEN status = "pending" THEN 1 ELSE 0 END) as pending_apartments
            ', [$startOfMonth])
            ->first();

        return response()->json([
            "status" => "success",
            "data" => [
                "total_views" => (int) ($stats->total_views ?? 0),
                "phone_clicks" => (int) ($stats->phone_clicks ?? 0),
                "monthly_views" => (int) ($stats->monthly_views ?? 0),
                "published_apartments" => (int) ($stats->published_apartments ?? 0),
                "pending_apartments" => (int) ($stats->pending_apartments ?? 0),
            ]
        ]);
    }
}