<?php

namespace App\Services;

use App\Helpers\SettingsHelper;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsappService {
    protected string $userCode;
    protected string $secret;
    protected string $deviceId;
    protected string $baseUrl;

    public function __construct() 
    {
        $this->userCode = config('services.kirimi.user_code');
        $this->secret = config('services.kirimi.secret');
        $this->deviceId = SettingsHelper::get('notifications.whatsapp.device_id');
        $this->baseUrl = 'https://api.kirimi.id/v1';
    }

    public function sendMessage(string $receiver, string $message) {
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
    
}