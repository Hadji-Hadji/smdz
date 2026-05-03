<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Admin;

class AdminCheck
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @param  string|null  ...$roles  // إضافة parameter لقبول الأدوار المطلوبة
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next, ...$roles)
    {
        if (!Auth::check()) {
            return response()->json(["message" => "Unauthenticated."], 401);
        }

        $user = Auth::user();

        // التحقق مما إذا كان المستخدم لديه سجل في جدول Admins
        if (!$user->admin) {
            return response()->json(["message" => "Unauthorized action. Admin access required."], 403);
        }

        // التحقق من الدور إذا تم تحديد أدوار معينة في الـ Middleware
        if (!empty($roles)) {
            if (!in_array($user->admin->role, $roles)) {
                return response()->json(["message" => "Unauthorized action. Insufficient role."], 403);
            }
        }

        return $next($request);
    }
}