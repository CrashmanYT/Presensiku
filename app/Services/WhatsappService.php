<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Support\PhoneNumber;

/**
 * Service wrapper for Kirimi WhatsApp API.
 *
 * Responsibilities:
 * - Send text messages to a WhatsApp receiver via Kirimi API.
 * - Send document attachments (e.g., PDF) via Kirimi API, with a safe fallback
 *   to sending a message containing a media URL when direct document send fails.
 *
 * Configuration keys (config/services.php):
 * - services.kirimi.user_code
 * - services.kirimi.secret
 * - services.kirimi.device_id
 */
class WhatsappService
{
    protected string $userCode;
    protected string $secret;
    protected ?string $deviceId;
    protected string $baseUrl;

    /**
     * Construct the WhatsappService using app configuration.
     */
    public function __construct()
    {
        $this->userCode = config('services.kirimi.user_code');
        $this->secret = config('services.kirimi.secret');
        $this->deviceId = config('services.kirimi.device_id');
        $this->baseUrl = 'https://api.kirimi.id/v1';
    }

    /**
     * Send a WhatsApp text message.
     *
     * Side effects:
     * - Performs an outbound HTTP request to Kirimi API.
     * - Writes info/error logs with the HTTP response.
     *
     * @param string $receiver Target number in international format, e.g. 6285xxxxxxxx
     * @param string $message  Text message body
     * @return array{success: bool, data?: mixed, error?: string}
     */
    public function sendMessage(string $receiver, string $message)
    {
        if (empty($this->deviceId)) {
            Log::warning('WhatsApp message not sent: Device ID is not configured.');
            return ['success' => false, 'error' => 'Device ID is not configured.'];
        }

        // Normalize and validate receiver number
        $receiver = PhoneNumber::normalize($receiver);
        if (!$receiver) {
            Log::warning('WhatsApp message not sent: Invalid receiver number.');
            return ['success' => false, 'error' => 'Invalid receiver number.'];
        }

        try {
            $response = Http::post("{$this->baseUrl}/send-message", [
                'user_code' => $this->userCode,
                'secret' => $this->secret,
                'device_id' => $this->deviceId,
                'receiver' => $receiver,
                'message' => $message,
                'enableTypingEffect' => true,
                'typingSpeedMs' => 350,
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
     * It first tries the primary document endpoint, and on failure falls back to
     * sending a message with a media URL as per Kirimi documentation.
     *
     * Side effects:
     * - Performs one or more outbound HTTP requests to Kirimi API.
     * - Writes info/warning/error logs with the HTTP responses.
     *
     * @param string      $receiver    Target number in international format, e.g. 6285xxxxxxxx
     * @param string      $documentUrl Publicly accessible URL to the document
     * @param string|null $caption     Optional caption to accompany the document
     * @param string|null $fileName    Optional filename hint (e.g., report.pdf)
     * @return array{success: bool, data?: mixed, error?: string}
     */
    public function sendDocument(string $receiver, string $documentUrl, ?string $caption = null, ?string $fileName = null): array
    {
        if (empty($this->deviceId)) {
            Log::warning('WhatsApp document not sent: Device ID is not configured.');
            return ['success' => false, 'error' => 'Device ID is not configured.'];
        }

        // Normalize and validate receiver number
        $receiver = PhoneNumber::normalize($receiver);
        if (!$receiver) {
            Log::warning('WhatsApp document not sent: Invalid receiver number.');
            return ['success' => false, 'error' => 'Invalid receiver number.'];
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

            // Fallback: use send-message with media_url as per Kirimi docs
            Log::warning('Primary document endpoint failed, trying send-message with media_url fallback.', ['response' => $responseData]);
            $fallbackPayload = [
                'user_code' => $this->userCode,
                'secret' => $this->secret,
                'device_id' => $this->deviceId,
                'receiver' => $receiver,
                'message' => $caption ?? '',
                'media_url' => $documentUrl,
                'enableTypingEffect' => true,
                'typingSpeedMs' => 350,
            ];
            $fallbackResponse = Http::post("{$this->baseUrl}/send-message", $fallbackPayload);
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
