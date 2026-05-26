<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ImageModerationService
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

    /**
     * فحص مصفوفة من الصور
     */
    public function checkMultiple(array $files): array
    {
        $prompt = $this->buildPrompt();

        foreach ($files as $index => $file) {
            try {
                $imageData = base64_encode(file_get_contents($file->getRealPath()));
                $mimeType = $file->getMimeType();
            } catch (\Throwable $e) {
                return [
                    'success' => false,
                    'status' => 500,
                    'message' => 'خطأ في معالجة الصورة رقم ' . ($index + 1) . ': ' . $e->getMessage(),
                ];
            }

            $imageVerifiedForThisModel = false;
            $lastErrorResult = null;

            foreach ($this->models as $model) {
                try {
                    $url = "{$this->baseUrl}/models/{$model}:generateContent?key={$this->apiKey}";

                    $response = Http::timeout(30)->post($url, [
                        'contents' => [
                            [
                                'parts' => [
                                    ['text' => $prompt],
                                    [
                                        'inlineData' => [
                                            'mimeType' => $mimeType,
                                            'data' => $imageData
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]);

                    if (!$response->successful()) {
                        Log::warning('Gemini Image API error', [
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

                    $text = preg_replace('/```json|```/', '', $text);
                    $decoded = json_decode(trim($text), true);

                    if (json_last_error() !== JSON_ERROR_NONE) {
                        Log::error('Image JSON decode failed', ['raw' => $text]);
                        continue;
                    }

                    // إذا تم الفحص بنجاح بواسطة هذا الموديل
                    $imageVerifiedForThisModel = true;

                    // إذا وُجدت صورة واحدة غير آمنة، نوقف الفحص فوراً ونعيد تفاصيل الرفض
                    if (isset($decoded['is_safe']) && $decoded['is_safe'] === false) {
                        return [
                            'success' => true,
                            'is_safe' => false,
                            'model' => $model,
                            'failed_image_index' => $index, // ترتيب الصورة التي فشلت (يبدأ من 0)
                            'data' => $decoded
                        ];
                    }

                    // الصورة الحالية آمنة، نخرج من مصفوفة الموديلات لننتقل للصورة التالية في الـ Loop الرئيسي
                    break; 

                } catch (\Throwable $e) {
                    Log::error('Gemini Image exception', [
                        'model' => $model,
                        'message' => $e->getMessage(),
                    ]);
                    $lastErrorResult = [
                        'success' => false,
                        'status' => 500,
                        'message' => 'حدث خطأ أثناء فحص الصورة رقم ' . ($index + 1),
                    ];
                    continue;
                }
            }

            // إذا انتهت الموديلات ولم تنجح أي منها في فحص الصورة الحالية بسبب خطأ اتصال أو ما شابه
            if (!$imageVerifiedForThisModel) {
                return $lastErrorResult ?? [
                    'success' => false,
                    'status' => 503,
                    'message' => 'فشل الاتصال بمزود خدمة الذكاء الاصطناعي عند فحص الصورة رقم ' . ($index + 1),
                ];
            }
        }

        // إذا مرت جميع الصور في الـ Loop بنجاح وتأكدنا أنها آمنة
        return [
            'success' => true,
            'is_safe' => true,
            'model' => 'gemini-2.5-flash',
            'message' => 'كل الصور سليمة وتطابق معايير التطبيق.'
        ];
    }

    private function buildPrompt(): string
    {
        return <<<PROMPT
You are an expert AI content moderator for a real estate renting application in Algeria.
Analyze this image carefully.

⚠️ CRITICAL RULES:
- The app is for apartment and house rentals only.
- Reject the image if it contains: nudity, weapons, violence, hate speech, blood, or people's faces clearly visible.
- Reject the image if it is completely unrelated to real estate (e.g., memes, cars, animals, personal selfies, random graphics).
- Accept the image if it shows rooms, kitchens, buildings, salons, views from windows, or furniture related to accommodation.

Return ONLY a JSON object without markdown or extra explanation:
{
  "is_safe": true or false,
  "reason": "Brief explanation in Arabic why it was accepted or rejected"
}
PROMPT;
    }
}