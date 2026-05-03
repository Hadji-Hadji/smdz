<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\GeminiService;
use Illuminate\Http\Request;

class AiAssistantController extends Controller
{
    public function suggestContent(Request $request, GeminiService $gemini)
    {
        $data = $request->validate([
            'province' => 'required|string',
            'city' => 'required|string',
            'rooms' => 'required|integer',
            'price' => 'nullable|numeric',
        ]);

        $result = $gemini->generateApartmentContent($data);

        // 🔴 فشل كامل
        if (!$result['success']) {

            // حالة overload (503)
            if (($result['status'] ?? null) === 503) {
                return response()->json([
                    'success' => false,
                    'status' => 'overloaded',
                    'message' => 'AI is busy, please wait a few seconds and try again'
                ], 503);
            }

            return response()->json([
                'success' => false,
                'status' => 'failed',
                'message' => $result['message'] ?? 'AI failed'
            ], 500);
        }

        // ✔ نجاح
        return response()->json([
            'success' => true,
            'model' => $result['model'],
            'data' => $result['data']
        ]);
    }
}
