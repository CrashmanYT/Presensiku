<?php

namespace Tests\Unit\Services;

use App\Services\WhatsappService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class WhatsappServiceTest extends TestCase
{
    private WhatsappService $whatsappService;

    protected function setUp(): void
    {
        parent::setUp();
        // Mock config values to avoid dependency on .env file during tests
        config([
            'services.kirimi.user_code' => 'test_user_code',
            'services.kirimi.secret' => 'test_secret',
            'services.kirimi.device_id' => 'test_device_id',
        ]);
        $this->whatsappService = new WhatsappService();
    }

    public function test_it_sends_message_successfully()
    {
        Http::fake([
            'api.kirimi.id/v1/send-message' => Http::response(['success' => true, 'data' => 'Message sent'], 200),
        ]);

        Log::shouldReceive('info')->once();

        $result = $this->whatsappService->sendMessage('6281234567890', 'Hello World');

        $this->assertTrue($result['success']);
        Http::assertSent(function (Request $request) {
            return $request->url() == 'https://api.kirimi.id/v1/send-message' &&
                   $request['receiver'] == '6281234567890' &&
                   $request['message'] == 'Hello World';
        });
    }

    public function test_it_handles_failed_response()
    {
        Http::fake([
            'api.kirimi.id/v1/send-message' => Http::response(['success' => false, 'message' => 'Gagal kirim pesan'], 400),
        ]);

        Log::shouldReceive('error')->once();

        $result = $this->whatsappService->sendMessage('6281234567890', 'Test message');

        $this->assertFalse($result['success']);
        $this->assertEquals('Gagal kirim pesan', $result['error']);
    }

    public function test_it_handles_http_exception()
    {
        // Simulate a client or server error without a specific JSON response
        Http::fake([
            'api.kirimi.id/v1/send-message' => Http::response(null, 500),
        ]);

        Log::shouldReceive('error')->once();

        $result = $this->whatsappService->sendMessage('6281234567890', 'Test message');

        $this->assertFalse($result['success']);
        // The service should return the generic 'Unknown error' when the response body is not the expected JSON.
        $this->assertEquals('Unknown error', $result['error']);
    }
}
