<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ChangePasswordRequest extends FormRequest
{
    public function authorize()
    {
        return true; // أي مستخدم مصادق عليه يمكنه تغيير كلمة المرور
    }

    public function rules()
    {
        return [
            "current_password" => "required|string",
            "new_password" => "required|string|min:6|confirmed", // يجب أن يرسل new_password_confirmation
        ];
    }

    public function messages()
    {
        return [
            "current_password.required" => "يرجى إدخال كلمة المرور الحالية",
            "new_password.required" => "يرجى إدخال كلمة المرور الجديدة",
            "new_password.min" => "يجب أن تكون كلمة المرور الجديدة على الأقل 6 أحرف",
            "new_password.confirmed" => "تأكيد كلمة المرور الجديدة غير مطابق",
        ];
    }
}