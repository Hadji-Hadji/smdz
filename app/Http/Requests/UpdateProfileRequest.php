<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
{
    public function authorize()
    {
        return true; // أي مستخدم مسجل يمكنه التحديث
    }

    public function rules()
    {
        $userId = auth('api')->id(); // بدل auth()->id()

        return [
            "name" => "sometimes|string|max:255",
            "email" => [
                "sometimes",
                "email",
                Rule::unique("users")->ignore($userId),
            ],
            "phone" => "sometimes|string|max:20",
        ];
    }

    public function messages()
    {
        return [
            "name.string" => "الاسم يجب أن يكون نصاً.",
            "name.max" => "الاسم طويل جداً.",
            "email.email" => "البريد الإلكتروني غير صالح.",
            "email.unique" => "البريد الإلكتروني مسجل مسبقًا.",
            "phone.string" => "رقم الهاتف يجب أن يكون نصاً.",
            "phone.max" => "رقم الهاتف طويل جداً.",
        ];
    }
}
