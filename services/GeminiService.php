<?php

require_once __DIR__ . '/../config/gemini.php';

class GeminiService
{
    private $apiKey;
    private $model;

    public function __construct()
    {
        $this->apiKey = GEMINI_API_KEY ?? '';
        $this->model = GEMINI_MODEL ?? 'gemini-3.5-flash';

        if (empty($this->apiKey)) {
            throw new Exception('Gemini API key belum dikonfigurasi. Tambahkan variabel environment GEMINI_API_KEY atau file config/gemini.local.php yang tidak di-commit.');
        }
    }


    public function generateResponse($contents)
    {
        // Kompatibilitas dengan pemanggilan lama yang mengirim string
        if (is_string($contents)) {
            $contents = [
                [
                    "role" => "user",
                    "parts" => [
                        ["text" => $contents]
                    ]
                ]
            ];
        }

        $url = "https://generativelanguage.googleapis.com/v1beta/models/"
            . $this->model . ":generateContent";

        $payload = [
            "contents" => $contents
        ];

        $delays = [2, 4, 8];

        for ($attempt = 0; $attempt < 3; $attempt++) {

            $ch = curl_init($url);

            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_HTTPHEADER => [
                    "Content-Type: application/json",
                    "x-goog-api-key: " . $this->apiKey
                ],
                CURLOPT_POSTFIELDS => json_encode($payload),
                CURLOPT_TIMEOUT => 60
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            if ($response === false) {
                $error = curl_error($ch);
                curl_close($ch);

                throw new Exception("cURL Error: " . $error);
            }

            curl_close($ch);

            $result = json_decode($response, true);

            if ($httpCode === 200) {
                return $result['candidates'][0]['content']['parts'][0]['text']
                    ?? 'Gemini tidak memberikan jawaban.';
            }

            // Retry jika server overload
            if ($httpCode === 503 && $attempt < 2) {
                sleep($delays[$attempt]);
                continue;
            }

            $error = $result['error']['message'] ?? 'Gemini API gagal';

            throw new Exception(
                "HTTP " . $httpCode . ": " . $error
            );
        }

        throw new Exception("Gemini gagal setelah beberapa percobaan.");
    }
}
