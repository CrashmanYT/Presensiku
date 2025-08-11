<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsappService {
    protected string $userCode;
    protected string $secret;
    protected ?string $deviceId;
    protected string $baseUrl;

    public function __construct() 
    {
        $this->userCode = config('services.kirimi.user_code');
        $this->secret = config('services.kirimi.secret');
        $this->deviceId = config('services.kirimi.device_id');
        $this->baseUrl = 'https://api.kirimi.id/v1';
    }

    public function sendMessage(string $receiver, string $message) {
        if (empty($this->deviceId)) {
            Log::warning('WhatsApp message not sent: Device ID is not configured.');
            return ['success' => false, 'error' => 'Device ID is not configured.'];
        }

        try {
            $response = Http::post("{$this->baseUrl}/send-message", [
                'user_code' => $this->userCode,
                'secret' => $this->secret,
                'device_id' => $this->deviceId,
                'receiver' => $receiver,
                'message' => $message,
                'enableTypingEffect' => true,
                'typingSpeedMS' => 350,
            ]);

            $responseData = $response->json();
            if ($response->successful()) {
                Log::info("WhatsApp message sent successfully to {$receiver}.", ['response' => $responseData]);
                return ['success' => true, 'data' => $responseData];
            } else {
                Log::error("Failed to send WhatsApp message to {$receiver}.", ['response' => $responseData]);
                return ['success' => false, 'error' => $responseData['message'] ?? 'Unknown error'];
            }
        } catch (Exception $e) {
            Log::error("Exception while sending WhatsApp message to {$receiver}: " . $e->getMessage(), ['exception' => $e]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Send a document (e.g., PDF) to a WhatsApp number using Kirimi API.
     * Will try the primary endpoint and fall back to a generic media endpoint if available.
     *
     * @param string $receiver E.g., 628xxxxxxxxxx
     * @param string $documentUrl Publicly accessible URL to the document
     * @param string|null $caption Optional caption
     * @param string|null $fileName Optional file name hint (e.g., report.pdf)
     * @return array{success: bool, data?: mixed, error?: string}
     */
    public function sendDocument(string $receiver, string $documentUrl, ?string $caption = null, ?string $fileName = null): array
    {
        if (empty($this->deviceId)) {
            Log::warning('WhatsApp document not sent: Device ID is not configured.');
            return ['success' => false, 'error' => 'Device ID is not configured.'];
        }

        try {
            // Primary: send-document
            $payload = [
                'user_code' => $this->userCode,
                'secret' => $this->secret,
                'device_id' => $this->deviceId,
                'receiver' => $receiver,
                'documentUrl' => $documentUrl,
                'caption' => $caption,
                'fileName' => $fileName,
            ];
            $response = Http::post("{$this->baseUrl}/send-document", $payload);
            $responseData = $response->json();
            if ($response->successful()) {
                Log::info("WhatsApp document sent successfully to {$receiver}.", ['response' => $responseData]);
                return ['success' => true, 'data' => $responseData];
            }

            // Fallback: send-media with type=document
            Log::warning('Primary document endpoint failed, trying media endpoint fallback.', ['response' => $responseData]);
            $fallbackPayload = [
                'user_code' => $this->userCode,
                'secret' => $this->secret,
                'device_id' => $this->deviceId,
                'receiver' => $receiver,
                'type' => 'document',
                'url' => $documentUrl,
                'caption' => $caption,
                'fileName' => $fileName,
            ];
            $fallbackResponse = Http::post("{$this->baseUrl}/send-media", $fallbackPayload);
            $fallbackData = $fallbackResponse->json();
            if ($fallbackResponse->successful()) {
                Log::info("WhatsApp document sent via media endpoint to {$receiver}.", ['response' => $fallbackData]);
                return ['success' => true, 'data' => $fallbackData];
            }

            Log::error("Failed to send WhatsApp document to {$receiver}.", [
                'primary' => $responseData,
                'fallback' => $fallbackData ?? null,
            ]);
            return ['success' => false, 'error' => $fallbackData['message'] ?? $responseData['message'] ?? 'Unknown error'];
        } catch (Exception $e) {
            Log::error("Exception while sending WhatsApp document to {$receiver}: " . $e->getMessage(), ['exception' => $e]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
}