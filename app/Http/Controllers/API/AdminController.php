<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Apartment;
use App\Models\Package;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\User;
use App\Models\VerificationDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    // --- Admin Management ---
    public function indexAdmins()
    {
        $admins = Admin::with("user")->get();
        return response()->json(["admins" => $admins]);
    }

    public function storeAdmin(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "user_id" => "required|exists:users,id|unique:admins,user_id",
            "role" => "required|string|max:50",
            "permissions" => "nullable|array",
        ]);

        if ($validator->fails()) {
            return response()->json(["errors" => $validator->errors()], 422);
        }

        $admin = Admin::create($request->all());
        return response()->json(["message" => "Admin added successfully.", "admin" => $admin], 201);
    }

    public function updateAdmin(Request $request, $admin_id)
    {
        $admin = Admin::find($admin_id);
        if (!$admin) {
            return response()->json(["message" => "Admin not found."], 404);
        }

        $validator = Validator::make($request->all(), [
            "role" => "sometimes|required|string|max:50",
            "permissions" => "nullable|array",
        ]);

        if ($validator->fails()) {
            return response()->json(["errors" => $validator->errors()], 422);
        }

        $admin->update($request->all());
        return response()->json(["message" => "Admin updated successfully.", "admin" => $admin]);
    }

    public function destroyAdmin($admin_id)
    {
        // 1. البحث عن المدير
        $admin = Admin::find($admin_id);

        if (!$admin) {
            return response()->json([
                "status" => "error",
                "message" => "المسؤول غير موجود في النظام"
            ], 404);
        }

        // الاحتفاظ بالـ ID قبل الحذف لإرساله في الرد
        $deletedId = $admin->id;
        $userId = $admin->user_id;

        // 2. تنفيذ الحذف (حذف صلاحية الأدمن فقط)
        $admin->delete();

        // 3. الرد النهائي بنجاح (مفيد جداً للـ Front-end لتحديث القائمة)
        return response()->json([
            "status" => "success",
            "message" => "تم سحب صلاحيات المسؤول بنجاح",
            "data" => [
                "admin_id" => (int) $deletedId,
                "user_id"  => (int) $userId,
                "action"   => "permissions_revoked"
            ]
        ], 200);
    }
    // --- Packages Management ---
    public function indexPackages()
    {
        $packages = Package::all();
        return response()->json(["packages" => $packages]);
    }

   // --- Packages Management ---

    /**
     * إنشاء باقة جديدة مع التحقق من هيكل الـ JSON في حقل features
     */
    public function storePackage(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "name" => "required|string|max:255",
            "description" => "nullable|string",
            "price" => "required|numeric|min:0",
            "duration_in_days" => "required|integer|min:1",

            // التحقق من أن features مصفوفة وتحتوي على البيانات المطلوبة
            "features" => "required|array",
            "features.max_listings" => "required|integer|min:0", // الحد الأقصى للشقق (إلزامي)
            "features.listing_duration" => "nullable|string",
            "features.visibility_rank" => "nullable|string",
            "features.max_images" => "nullable|integer|min:1",
            "features.analytics" => "nullable|string",
            "features.featured_ads" => "nullable|boolean",

            "is_active" => "boolean",
        ]);

        if ($validator->fails()) {
            return response()->json(["errors" => $validator->errors()], 422);
        }

        // بفضل الـ Casting في موديل Package، سيتم تحويل المصفوفة تلقائياً إلى JSON
        $package = Package::create($request->all());

        return response()->json([
            "status" => "success",
            "message" => "Package created successfully.",
            "package" => $package
        ], 201);
    }

    /**
     * تحديث باقة موجودة مع إمكانية تحديث أجزاء من حقل features
     */
    public function updatePackage(Request $request, $package_id)
    {
        $package = Package::find($package_id);
        if (!$package) {
            return response()->json(["message" => "Package not found."], 404);
        }

        $validator = Validator::make($request->all(), [
            "name" => "sometimes|required|string|max:255",
            "description" => "nullable|string",
            "price" => "sometimes|required|numeric|min:0",
            "duration_in_days" => "sometimes|required|integer|min:1",

            // في التحديث نستخدم sometimes لكي لا يضطر المسؤول لإرسال كل الحقول
            "features" => "sometimes|required|array",
            "features.max_listings" => "sometimes|required|integer|min:0",
            "features.listing_duration" => "nullable|string",
            "features.visibility_rank" => "nullable|string",
            "features.max_images" => "nullable|integer|min:1",
            "features.analytics" => "nullable|string",
            "features.featured_ads" => "nullable|boolean",

            "is_active" => "boolean",
        ]);

        if ($validator->fails()) {
            return response()->json(["errors" => $validator->errors()], 422);
        }

        $package->update($request->all());

        return response()->json([
            "status" => "success",
            "message" => "Package updated successfully.",
            "package" => $package
        ]);
    }

    public function destroyPackage($package_id)
    {
        // 1. البحث عن الباقة
        $package = Package::find($package_id);

        if (!$package) {
            return response()->json([
                "status" => "error",
                "message" => "الباقة غير موجودة"
            ], 404);
        }

        // 2. التحقق من وجود اشتراكات مرتبطة (حماية لقاعدة البيانات)
        // نتحقق مما إذا كان هناك أي مستخدم مشترك حالياً في هذه الباقة
        $hasActiveSubscriptions = $package->subscriptions()->exists();

        if ($hasActiveSubscriptions) {
            return response()->json([
                "status" => "error",
                "message" => "لا يمكن حذف هذه الباقة لوجود مستخدمين مشتركين فيها حالياً. يمكنك تعطيلها (is_active = false) بدلاً من الحذف."
            ], 422); // كود 422 للعمليات غير القابلة للتنفيذ منطقياً
        }

        // 3. تنفيذ الحذف
        $package->delete();

        // 4. الرد بنجاح مع الهيكل الموحد
        return response()->json([
            "status" => "success",
            "message" => "تم حذف الباقة بنجاح من النظام",
            "data" => [
                "package_id" => (int) $package_id
            ]
        ], 200);
    }

    // --- Payments Management ---
    public function indexPendingPayments()
    {
        $payments = Payment::where("status", "pending_verification")->with("user", "package")->get();
        return response()->json(["pending_payments" => $payments]);
    }

    public function showPayment($payment_id)
    {
        $payment = Payment::with("user", "package")->find($payment_id);
        if (!$payment) {
            return response()->json(["message" => "Payment not found."], 404);
        }
        return response()->json(["payment" => $payment]);
    }

    public function approvePayment(Request $request, $payment_id)
    {
        // 1. البحث عن عملية الدفع والتأكد من وجودها
        $payment = Payment::with(['user', 'package'])->find($payment_id);

        if (!$payment) {
            return response()->json(["message" => "Payment not found."], 404);
        }

        // 2. التأكد من أن الحالة هي 'بانتظار التحقق' فقط
        if ($payment->status !== "pending_verification") {
            return response()->json(["message" => "Payment is not pending verification."], 400);
        }

        $package = $payment->package;
        if (!$package) {
            return response()->json(["message" => "Associated package not found."], 404);
        }

        // 3. البدء في عملية التحديث (Transaction) لضمان دقة البيانات
        return DB::transaction(function () use ($payment, $package, $request) {

            // أ. تحديث حالة الدفع
            $payment->status = "approved";
            $payment->admin_notes = $request->input("admin_notes");
            $payment->save();

            // ب. تعطيل أي اشتراكات نشطة قديمة للمستخدم (مثل الباقة المجانية أو باقة منتهية لم تُعطل)
            Subscription::where('user_id', $payment->user_id)
                ->where('is_active', true)
                ->update(['is_active' => false]);

            // ج. إنشاء الاشتراك الجديد بناءً على الباقة المدفوعة
            $startDate = now();
            $endDate = $startDate->copy()->addDays($package->duration_in_days);

            $subscription = Subscription::create([
                "user_id" => $payment->user_id,
                "package_id" => $payment->package_id,
                "start_date" => $startDate,
                "end_date" => $endDate,
                "is_active" => true,
            ]);

            return response()->json([
                "status" => "success",
                "message" => "Payment approved. Old subscriptions deactivated and new package activated.",
                "payment" => $payment,
                "subscription" => $subscription,
            ]);
        });
    }

    public function rejectPayment(Request $request, $payment_id)
    {
        $payment = Payment::find($payment_id);
        if (!$payment) {
            return response()->json(["message" => "Payment not found."], 404);
        }

        if ($payment->status !== "pending_verification") {
            return response()->json(["message" => "Payment is not pending verification."], 400);
        }

        $validator = Validator::make($request->all(), [
            "admin_notes" => "required|string",
        ]);

        if ($validator->fails()) {
            return response()->json(["errors" => $validator->errors()], 422);
        }

        $payment->status = "rejected";
        $payment->admin_notes = $request->input("admin_notes");
        $payment->save();

        return response()->json(["message" => "Payment rejected successfully.", "payment" => $payment]);
    }

    // --- Subscriptions Management ---
    public function indexSubscriptions()
    {
        $subscriptions = Subscription::with("user", "package")->get();
        return response()->json(["subscriptions" => $subscriptions]);
    }

    public function updateSubscriptionStatus(Request $request, $subscription_id)
    {
        $subscription = Subscription::find($subscription_id);
        if (!$subscription) {
            return response()->json(["message" => "Subscription not found."], 404);
        }

        $validator = Validator::make($request->all(), [
            "is_active" => "required|boolean",
        ]);

        if ($validator->fails()) {
            return response()->json(["errors" => $validator->errors()], 422);
        }

        $subscription->is_active = $request->input("is_active");
        $subscription->save();

        return response()->json(["message" => "Subscription status updated successfully.", "subscription" => $subscription]);
    }

    // --- Verification Documents Management ---


    public function indexPendingVerifications()
    {
        $pendingVerifications = VerificationDocument::where("status", "pending")
            ->with("user:id,name,email,phone,verification_status")
            ->get();

        return response()->json([
            "status" => "success",
            "data" => $pendingVerifications->map(function ($verification) {
                return [
                    "id" => $verification->id,
                    "user_id" => $verification->user->id,
                    "user_name" => $verification->user->name,
                    "user_email" => $verification->user->email,
                    "document_type" => $verification->document_type,
                    "document_path" => Storage::url($verification->document_path),
                    "status" => $verification->status,
                    "created_at" => $verification->created_at->format("Y-m-d H:i:s"),
                ];
            })
        ]);
    }


    public function approveVerification($verification_id)
    {
        $verification = VerificationDocument::with("user")->find($verification_id);

        if (!$verification) {
            return response()->json(["status" => "error", "message" => "وثيقة التحقق غير موجودة."], 404);
        }

        if ($verification->status !== "pending") {
            return response()->json(["status" => "error", "message" => "وثيقة التحقق ليست في حالة الانتظار."], 400);
        }

        DB::transaction(function () use ($verification) {
            $verification->status = "approved";
            $verification->save();

            // تحديث حالة المستخدم إلى 'verified'
            $verification->user->verification_status = "verified";
            $verification->user->save();
        });

        return response()->json([
            "status" => "success",
            "message" => "تم قبول وثيقة التحقق وتحديث حالة المستخدم بنجاح.",
            "data" => [
                "verification" => $verification,
                "user_verification_status" => $verification->user->verification_status
            ]
        ]);
    }


    public function rejectVerification(Request $request, $verification_id)
    {
        $verification = VerificationDocument::with("user")->find($verification_id);

        if (!$verification) {
            return response()->json(["status" => "error", "message" => "وثيقة التحقق غير موجودة."], 404);
        }

        if ($verification->status !== "pending") {
            return response()->json(["status" => "error", "message" => "وثيقة التحقق ليست في حالة الانتظار."], 400);
        }

        $validator = Validator::make($request->all(), [
            "admin_notes" => "required|string|max:500",
        ]);

        if ($validator->fails()) {
            return response()->json(["errors" => $validator->errors()], 422);
        }

        DB::transaction(function () use ($verification, $request) {
            $verification->status = "rejected";
            // إذا أردنا، يمكن تخزين سبب الرفض:
            // $verification->admin_notes = $request->input("admin_notes");
            $verification->save();

            // تحديث حالة المستخدم إلى 'rejected'
            $verification->user->verification_status = "rejected";
            $verification->user->save();
        });

        return response()->json([
            "status" => "success",
            "message" => "تم رفض وثيقة التحقق وتحديث حالة المستخدم بنجاح.",
            "data" => [
                "verification" => $verification,
                "user_verification_status" => $verification->user->verification_status
            ]
        ]);
    }


    // ادارة الشقق
    /**
     * عرض جميع الشقق مع بيانات المالك
     */
    public function indexApartments(Request $request)
    {
        // ابدأ الاستعلام مع جلب بيانات المالك
        $query = Apartment::with('landlord');

        // الفلترة حسب الحالة إذا تم إرسالها في الرابط (مثلاً: ?status=pending)
        if ($request->has('status') && in_array($request->status, ['pending', 'approved', 'rejected'])) {
            $query->where('status', $request->status);
        }

        // ترتيب الشقق من الأحدث إلى الأقدم
        $apartments = $query->latest()->get();

        return response()->json([
            'status' => 'success',
            'count' => $apartments->count(),
            'data' => $apartments
        ]);
    }

    /**
     * قبول الشقة فقط (دون المساس بتوثيق حساب المالك)
     */
    public function approveApartment(Request $request, $id)
    {
        $apartment = Apartment::find($id);

        if (!$apartment) {
            return response()->json(['message' => 'Apartment not found.'], 404);
        }

        if ($apartment->status === 'approved') {
            return response()->json(['message' => 'Apartment is already approved.'], 400);
        }

        // تحديث الشقة فقط
        $apartment->update([
            'status' => 'approved',
            'is_active' => true,
            'admin_notes' => null // مسح أي ملاحظات رفض سابقة بما أننا قبلناها الآن
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Apartment approved successfully. It is now visible to users.',
            'apartment' => $apartment
        ]);
    }

    /**
     * رفض الشقة مع تسجيل السبب
     */
    public function rejectApartment(Request $request, $id)
    {
        $apartment = Apartment::find($id);

        if (!$apartment) {
            return response()->json(['message' => 'Apartment not found.'], 404);
        }

        $validator = Validator::make($request->all(), [
            'reason' => 'required|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $apartment->update([
            'status' => 'rejected',
            'is_active' => false, // إخفاء من العرض العام فوراً
            'admin_notes' => $request->reason
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Apartment rejected.',
            'apartment' => $apartment
        ]);
    }
    /**
     * عرض تفاصيل شقة محددة للمسؤول
     */
    public function showApartment($id)
    {
        // جلب الشقة مع بيانات المالك (الاسم، الهاتف، الإيميل) 
        $apartment = Apartment::with(['landlord:id,name,email,phone,verification_status'])
            ->find($id);

        if (!$apartment) {
            return response()->json([
                'status' => 'error',
                'message' => 'الشقة غير موجودة.'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $apartment
        ]);
    }





    //ادارة المستخدمين
    /**
     * عرض قائمة بجميع المستخدمين (باستثناء المسؤولين).
     */
    public function listUsers()
    {
        // جلب المستخدمين الذين ليس لديهم سجل في جدول admins
        $users = User::whereDoesntHave('admin')
            ->with('activeSubscription') // اختياري: إذا أردت رؤية حالة اشتراكهم في القائمة
            ->get();

        return response()->json([
            'status' => 'success',
            'count'  => $users->count(),
            'users'  => $users
        ]);
    }

    /**
     * عرض تفاصيل مستخدم محدد (بشرط ألا يكون مسؤولاً).
     */
    public function showUser($id)
    {
        // نبحث عن المستخدم مع التأكد أنه ليس لديه سجل في جدول admins
        $user = User::whereDoesntHave('admin')
            ->with(['activeSubscription', 'subscriptions', 'apartments']) // أضفنا العلاقات التي تهم الإدارة
            ->find($id);

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'المستخدم غير موجود أو غير مصرح بعرض بياناته كزبون.'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'user' => $user
        ]);
    }

    /**
     * حذف مستخدم محدد.
     */
    public function deleteUser($id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json(['message' => 'المستخدم غير موجود.'], 404);
        }

        // منع حذف المستخدمين الذين لديهم سجل في جدول admins (حماية إضافية)
        if ($user->admin) {
            return response()->json([
                'status' => 'error',
                'message' => 'لا يمكن حذف مستخدم لديه صلاحيات إدارية.'
            ], 403);
        }

        $user->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'تم حذف المستخدم بنجاح.'
        ], 200);
    }


    /**
     * الحصول على إحصائيات عامة للوحة التحكم
     */
    public function getStats()
    {
        // 1. حساب عدد الشقق (الكلي، والمعلق للمراجعة)
        $totalApartments = Apartment::count();
        $pendingApartments = Apartment::where('status', 'pending')->count();

        // 2. حساب الاشتراكات النشطة (ليست مجانية ولديها تاريخ صلاحية سارٍ)
        $activeSubscriptions = Subscription::where('is_active', true)
            ->where('end_date', '>', now())
            ->count();

        // 3. إجمالي المداخيل لهذا الشهر (فقط العمليات المقبولة)
        $thisMonthRevenue = Payment::where('status', 'approved')
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('amount');

        // 4. طلبات التوثيق المعلقة
        $pendingVerifications = VerificationDocument::where('status', 'pending')->count();

        // 5. إجمالي المستخدمين (الملاك والزبائن)
        $totalUsers = User::whereDoesntHave('admin')->count();

        return response()->json([
            "status" => "success",
            "data" => [
                "apartments" => [
                    "total" => $totalApartments,
                    "pending" => $pendingApartments,
                ],
                "subscriptions" => [
                    "active_paid" => $activeSubscriptions,
                ],
                "revenue" => [
                    "this_month" => round($thisMonthRevenue, 2),
                    "currency" => "DZD" // أو العملة التي تعتمدها
                ],
                "verifications" => [
                    "pending" => $pendingVerifications,
                ],
                "users" => [
                    "total_clients" => $totalUsers
                ],
                "timestamp" => now()->format('Y-m-d H:i:s')
            ]
        ]);
    }
}
