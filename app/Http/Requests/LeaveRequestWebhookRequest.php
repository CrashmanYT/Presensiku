<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Request validator for student/teacher leave submissions via webhook.
 *
 * Expected payload fields:
 * - identifier: NIS (student) or NIP (teacher)
 * - start_date: YYYY-MM-DD
 * - end_date: YYYY-MM-DD (>= start_date)
 * - type: one of [Sakit, Izin]
 * - reason: free text reason
 * - attachment_url: optional URL to supporting document
 */
class LeaveRequestWebhookRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * Uses a shared secret header for simple webhook authentication.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return $this->header('X-Webhook-Secret') === config('services.webhook.secret_token');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'identifier' => 'required|string|max:255',
            'start_date' => 'required|date_format:Y-m-d',
            'type' => 'required|string|in:Sakit,Izin',
            'end_date' => 'required|date_format:Y-m-d|after_or_equal:start_date',
            'reason' => 'required|string',
            'attachment_url' => 'nullable|string',
        ];
    }
}
