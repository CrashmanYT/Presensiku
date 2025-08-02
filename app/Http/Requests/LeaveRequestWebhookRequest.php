<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LeaveRequestWebhookRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
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
