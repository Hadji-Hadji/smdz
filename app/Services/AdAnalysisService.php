<?php

namespace App\Services;

use App\Models\Apartment;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AdAnalysisService
{
    protected string $apiKey;
    protected string $baseUrl = 'https://generativelanguage.googleapis.com/v1';

    protected array $models = [
        'gemini-2.5-flash',
    ];

    public function __construct()
    {
        $this->apiKey = config('services.gemini.key');
    }

    public function analyze(Apartment $apartment): array
    {
        try {
            $prompt = $this->buildPrompt($apartment);
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'status' => 500,
                'message' => 'خطأ في تجهيز البيانات: ' . $e->getMessage(),
            ];
        }

        foreach ($this->models as $model) {

            try {
                $url = "{$this->baseUrl}/models/{$model}:generateContent?key={$this->apiKey}";

                $response = Http::timeout(30)->post($url, [
                    'contents' => [
                        [
                            'parts' => [
                                ['text' => $prompt]
                            ]
                        ]
                    ]
                ]);

                if (!$response->successful()) {
                    Log::warning('Gemini API error', [
                        'model' => $model,
                        'status' => $response->status(),
                        'body' => $response->body()
                    ]);

                    continue;
                }

                $text = data_get($response->json(), 'candidates.0.content.parts.0.text');

                if (!$text) {
                    continue;
                }

                // تنظيف JSON
                $text = preg_replace('/```json|```/', '', $text);

                $decoded = json_decode(trim($text), true);

                if (json_last_error() !== JSON_ERROR_NONE) {
                    Log::error('JSON decode failed', [
                        'raw' => $text
                    ]);

                    continue;
                }

                return [
                    'success' => true,
                    'model' => $model,
                    'data' => $decoded
                ];

            } catch (\Throwable $e) {
                Log::error('Gemini exception', [
                    'model' => $model,
                    'message' => $e->getMessage(),
                ]);

                continue;
            }
        }

        return [
            'success' => false,
            'status' => 503,
            'message' => 'فشل جميع نماذج Gemini',
        ];
    }

    private function buildPrompt(Apartment $apartment): string
    {
        $amenities = is_array($apartment->amenities)
            ? $apartment->amenities
            : json_decode($apartment->amenities ?? '[]', true);

        $amenitiesText = implode(', ', $amenities ?? []);

        return <<<PROMPT
أنت خبير عقاري محترف في الجزائر.

⚠️ IMPORTANT RULES:
- هذا النظام خاص بكراء الشقق فقط (RENT ONLY)
- لا يوجد بيع نهائياً
- price_unit = month يعني إيجار شهري
- لا تفترض أي معلومات غير موجودة في البيانات
- إذا معلومة غير موجودة اكتب "غير مذكور"

أعد JSON فقط بدون شرح:

{
  "score": 0,
  "feedback": "",
  "listing_type": "rent",
  "strengths": [],
  "weaknesses": [],
  "suggestions": [],
  "optimized_content": {
    "title": "",
    "description": ""
  }
}

DATA:
- العنوان: {$apartment->title}
- الوصف: {$apartment->description}
- الولاية: {$apartment->wilaya}
- البلدية: {$apartment->municipality}
- السعر: {$apartment->price} دج / {$apartment->price_unit}
- المساحة: {$apartment->area} m²
- الغرف: {$apartment->rooms}
- المرافق: {$amenitiesText}
PROMPT;
    }
}