<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Favorite;
use App\Models\Apartment;

class FavoriteController extends Controller
{
    // --- جلب المفضلة ---
    public function index()
    {
        $user = auth('api')->user();

        if (!$user) {
            return response()->json([
                "status" => "error",
                "message" => "Unauthorized"
            ], 401);
        }

        $favorites = Favorite::where("user_id", $user->id)
            ->with("apartment:id,title,wilaya,price,images")
            ->get();

        $data = $favorites->map(function ($favorite) {
            return [
                "id" => $favorite->apartment->id,
                "title" => $favorite->apartment->title,
                "wilaya" => $favorite->apartment->wilaya,
                "price" => $favorite->apartment->price,
                "images" => $favorite->apartment->images,
            ];
        });

        return response()->json([
            "status" => "success",
            "data" => $data
        ]);
    }

    // --- Toggle Favorite ---
    public function toggle($apartment_id)
    {
        $user = auth('api')->user();

        if (!$user) {
            return response()->json([
                "status" => "error",
                "message" => "Unauthorized"
            ], 401);
        }

        // 1️⃣ تحقق من وجود الشقة
        $apartment = Apartment::find($apartment_id);
        if (!$apartment) {
            return response()->json([
                "status" => "error",
                "message" => "الشقة غير موجودة"
            ], 404);
        }

        // 2️⃣ تحقق هل الشقة موجودة بالفعل في المفضلة
        $favorite = Favorite::where('user_id', $user->id)
            ->where('apartment_id', $apartment_id)
            ->first();

        if ($favorite) {
            // 3️⃣ موجودة → احذفها
            $favorite->delete();

            return response()->json([
                "status" => "success",
                "message" => "تمت إزالة الشقة من المفضلة"
            ]);
        } else {
            // 4️⃣ غير موجودة → أضفها
            Favorite::create([
                "user_id" => $user->id,
                "apartment_id" => $apartment_id
            ]);

            return response()->json([
                "status" => "success",
                "message" => "تمت إضافة الشقة للمفضلة"
            ]);
        }
    }
}