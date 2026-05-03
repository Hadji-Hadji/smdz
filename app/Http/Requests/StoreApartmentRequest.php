<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreApartmentRequest extends FormRequest
{
    public function authorize()
    {
        return auth('api')->check();
    }

    public function rules()
    {
        return [
            "title" => "required|string|max:255",
            "description" => "required|string",
            "wilaya" => "required|string|max:255",
            "municipality" => "required|string|max:255",
            "price" => "required|numeric|min:0",
            "price_unit" => "required|in:day,week,month",
            "rooms" => "required|integer|min:0",
            "bathrooms" => "required|integer|min:0",
            "area" => "required|numeric|min:0",
            "amenities" => "nullable|array",
            "amenities.*" => "string|max:100",
            "images" => "nullable|array",
            "images.*" => "image|mimes:jpeg,png,jpg,gif,svg|max:10240", // 10MB
        ];
    }

    public function messages()
    {
        return [
            "title.required" => "يرجى إدخال عنوان الشقة",
            "description.required" => "يرجى إدخال وصف الشقة",
            "price.required" => "يرجى إدخال السعر",
            "price_unit.required" => "يرجى تحديد وحدة السعر",
            "rooms.required" => "يرجى تحديد عدد الغرف",
            "bathrooms.required" => "يرجى تحديد عدد الحمامات",
            "area.required" => "يرجى تحديد المساحة",
        ];
    }
}