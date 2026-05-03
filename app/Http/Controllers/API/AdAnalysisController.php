<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Apartment;
use App\Services\AdAnalysisService;
use Illuminate\Http\Request;

class AdAnalysisController extends Controller
{
    public function __invoke(Request $request, AdAnalysisService $analysisService)
    {
        // ✅ validation
        $request->validate([
            'apartment_id' => 'required|integer|exists:apartments,id',
        ]);

        // ✅ auth check
        $user = auth('api')->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح'
            ], 401);
        }

        // ✅ get apartment
        $apartment = Apartment::find($request->apartment_id);

        if (!$apartment) {
            return response()->json([
                'success' => false,
                'message' => 'الشقة غير موجودة'
            ], 404);
        }

        // ✅ ownership check
        if ((int)$apartment->landlord_id !== (int)$user->id) {
            return response()->json([
                'success' => false,
                'message' => 'ليس لديك صلاحية تحليل هذا الإعلان'
            ], 403);
        }

        // ✅ AI analysis
        $result = $analysisService->analyze($apartment);

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['message'],
                'debug' => $result['debug'] ?? null
            ], $result['status'] ?? 500);
        }

        // ✅ success
        return response()->json([
            'success' => true,
            'model' => $result['model'],
            'report' => $result['data']
        ]);
    }
}