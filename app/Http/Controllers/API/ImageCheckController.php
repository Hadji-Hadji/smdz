<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\ImageModerationService;
use Illuminate\Http\Request;

class ImageCheckController extends Controller
{
    public function check(Request $request, ImageModerationService $moderationService)
    {
        // ✅ 1. validation (توقع مصفوفة صور باسم images)
        $request->validate([
            'images' => 'required|array|min:1',
            'images.*' => 'required|image|mimes:jpeg,png,jpg|max:5120',
        ]);

        // ✅ 2. auth check
        $user = auth('api')->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح'
            ], 401);
        }

        // ✅ 3. AI analysis لأكثر من صورة
        $result = $moderationService->checkMultiple($request->file('images'));

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['message']
            ], $result['status'] ?? 500);
        }

        // ✅ 4. التحقق مما إذا تم رفض أحد الصور
        if (isset($result['is_safe']) && $result['is_safe'] === false) {
            $report = $result['data'];
            $imageNumber = $result['failed_image_index'] + 1;

            return response()->json([
                'success' => false,
                'is_safe' => false,
                'failed_image_index' => $result['failed_image_index'],
                'message' => "الصورة رقم ({$imageNumber}) مرفوضة: " . ($report['reason'] ?? 'لا تطابق معايير العقارات.'),
                'model' => $result['model']
            ], 422);
        }

        // ✅ 5. success (كل الصور سليمة وآمنة)
        return response()->json([
            'success' => true,
            'is_safe' => true,
            'model' => $result['model'],
            'message' => $result['message']
        ], 200);
    }
}