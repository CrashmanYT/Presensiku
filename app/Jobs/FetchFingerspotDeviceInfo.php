<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Client\ConnectionException;

class FetchFingerspotDeviceInfo implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $cloudId;

    /**
     * Create a new job instance.
     */
    public function __construct(string $cloudId)
    {
        $this->cloudId = $cloudId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // Panggil API dengan timeout 10 detik
            $response = Http::withoutVerifying()
                ->timeout(10) // <-- TAMBAHAN: Batasi waktu tunggu hingga 10 detik
                ->post('https://developer.fingerspot.io/api/get_userinfo', [
                    'trans_id' => uniqid(),
                    'cloud_id' => $this->cloudId,
                ]);

            if ($response->successful()) {
                Log::channel('daily')->info('Device Info Received (from Job):', $response->json());
            } else {
                Log::error('Failed to fetch device info from Fingerspot API (from Job).', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
            }
        // Tangkap error timeout secara spesifik
        } catch (ConnectionException $e) {
            Log::error('Timeout when calling Fingerspot API (from Job): ' . $e->getMessage());
        } catch (\Exception $e) {
            Log::error('Exception when calling Fingerspot API (from Job): ' . $e->getMessage());
        }
    }
}