<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ListApartmentsRequest extends FormRequest
{
    public function authorize()
    {
        return true; // أي شخص يمكنه تصفح الشقق
    }

    public function rules()
    {
        return [
            "wilaya" => "sometimes|string|max:255",
            "municipality" => "sometimes|string|max:255",
            "price_unit" => "sometimes|in:day,week,month",
            "rooms" => "sometimes|integer|min:0",
            "min_price" => "sometimes|numeric|min:0",
            "max_price" => "sometimes|numeric|min:0",
            "sort_by" => "sometimes|in:latest,price_asc,price_desc",
        ];
    }
}