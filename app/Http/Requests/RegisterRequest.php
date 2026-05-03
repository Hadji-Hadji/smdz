<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            "name" => "required|string|max:255",
            "email" => "required|email|unique:users,email",
            "password" => "required|string|min:6|confirmed",
            "phone" => "nullable|string|max:20",
            "type" => "required|in:landlord,renter",
        ];
    }

    public function messages()
    {
        return [
            "name.required" => "حقل الاسم مطلوب.",
            "email.required" => "حقل البريد الإلكتروني مطلوب.",
            "email.email" => "البريد الإلكتروني غير صالح.",
            "email.unique" => "البريد الإلكتروني مسجل مسبقًا.",
            "password.required" => "حقل كلمة المرور مطلوب.",
            "password.min" => "كلمة المرور قصيرة جدًا.",
            "password.confirmed" => "تأكيد كلمة المرور لا يطابق.",
            "type.required" => "حقل نوع المستخدم مطلوب.",
            "type.in" => "نوع المستخدم يجب أن يكون landlord أو renter.",
        ];
    }
}