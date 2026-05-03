<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize()
    {
        return true; // السماح لكل المستخدمين
    }

    public function rules()
    {
        return [
            "email" => "required|email|exists:users,email",
            "password" => "required|string|min:6",
        ];
    }

    public function messages()
    {
        return [
            "email.required" => "حقل البريد الإلكتروني مطلوب.",
            "email.email" => "البريد الإلكتروني غير صالح.",
            "email.exists" => "البريد الإلكتروني غير مسجل.",
            "password.required" => "حقل كلمة المرور مطلوب.",
            "password.min" => "كلمة المرور قصيرة جداً.",
        ];
    }
}