<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateApartmentRequest extends FormRequest
{
    public function authorize()
    {
        // تحقق من تسجيل الدخول
        return auth('api')->check();
    }

    public function rules()
    {
        return [
            "title" => "sometimes|string|max:255",
            "description" => "sometimes|string",
            "wilaya" => "sometimes|string|max:255",
            "municipality" => "sometimes|string|max:255",
            "price" => "sometimes|numeric|min:0",
            "price_unit" => "sometimes|in:day,week,month",
            "rooms" => "sometimes|integer|min:0",
            "bathrooms" => "sometimes|integer|min:0",
            "area" => "sometimes|numeric|min:0",
            "amenities" => "sometimes|array",
            "amenities.*" => "string|max:100",
            "images" => "sometimes|array",
            "images.*" => "image|mimes:jpeg,png,jpg,gif,svg|max:10240", // 10MB
        ];
    }

    public function messages()
    {
        return [
            "title.string" => "العنوان يجب أن يكون نصًا",
            "price.numeric" => "السعر يجب أن يكون رقمًا",
            "price_unit.in" => "وحدة السعر يجب أن تكون day, week, أو month",
        ];
    }
}