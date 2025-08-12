<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

/**
 * Placeholder controller for leave (izin) related API endpoints.
 *
 * Currently provides a webhook stub that can be implemented to accept
 * external leave submissions if needed.
 */
class IzinController extends Controller
{
    /**
     * Webhook endpoint stub for incoming leave (izin) requests.
     *
     * @param Request $request
     * @return void
     */
    public function webhook(Request $request) {}
}
