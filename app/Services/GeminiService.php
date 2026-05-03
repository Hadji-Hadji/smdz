<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiService
{
    protected string $apiKey;
    protected string $baseUrl = 'https://generativelanguage.googleapis.com/v1beta';

    protected array $models = [
        'gemini-2.5-flash',
        'gemini-2.0-flash',
        'gemini-2.5-flash-lite',
    ];

    public function __construct()
    {
        $this->apiKey = config('services.gemini.key');
    }

    public function generateApartmentContent(array $data): array
    {
        $prompt = $this->buildPrompt($data);

        foreach ($this->models as $model) {

            try {
                $response = Http::timeout(30)->post(
                    "{$this->baseUrl}/models/{$model}:generateContent?key={$this->apiKey}",
                    [
                        'contents' => [
                            [
                                'parts' => [
                                    ['text' => $prompt]
                                ]
                            ]
                        ]
                    ]
                );

                // 🔴 API FAIL (503 / 429 / 404)
                if (!$response->successful()) {

                    $code = $response->status();

                    Log::warning("Gemini model failed", [
                        'model' => $model,
                        'status' => $code,
                        'body' => $response->body()
                    ]);

                    // إذا overload → جرّب التالي
                    if (in_array($code, [503, 429, 404])) {
                        continue;
                    }

                    return [
                        'success' => false,
                        'error' => 'API_ERROR',
                        'status' => $code,
                        'message' => 'Gemini API error'
                    ];
                }

                $text = data_get($response->json(), 'candidates.0.content.parts.0.text');

                if (!$text) {
                    continue;
                }

                $text = preg_replace('/```json|```/', '', $text);

                return [
                    'success' => true,
                    'model' => $model,
                    'data' => json_decode(trim($text), true)
                ];
            } catch (\Throwable $e) {

                Log::error('Gemini Exception', [
                    'model' => $model,
                    'message' => $e->getMessage()
                ]);

                continue;
            }
        }

        // كل الموديلات فشلت
        return [
            'success' => false,
            'error' => 'ALL_MODELS_FAILED',
            'message' => 'AI is currently overloaded, please try again later'
        ];
    }

private function buildPrompt(array $data): string
{
    return <<<PROMPT
أنت خبير تسويق عقاري محترف في الجزائر.

⚠️ IMPORTANT RULES (STRICT):
- هذا النظام خاص بكراء الشقق فقط (RENT ONLY)
- ممنوع استخدام أي كلمات مرتبطة بالبيع أو التملك مثل:
  * امتلاك
  * شراء
  * ملكية
  * استثمار
  * فرصة ذهبية للبيع
- استخدم فقط لغة الإيجار:
  * للإيجار
  * شهرياً
- لا تضف مبالغة تسويقية أو وعود وهمية
- لا تخترع معلومات غير موجودة

أعد JSON فقط بدون أي شرح:

{
  "title": "عنوان احترافي للإيجار فقط",
  "description": "وصف تسويقي مناسب للكراء فقط"
}

DATA:
- الولاية: {$data['province']}
- المدينة: {$data['city']}
- عدد الغرف: {$data['rooms']}
- السعر: {$data['price']} دج شهرياً
PROMPT;
}
}
