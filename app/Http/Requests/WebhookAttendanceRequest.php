<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class WebhookAttendanceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Webhook endpoint, no user authorization needed
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'type' => 'required|string',
            'cloud_id' => 'required|string',
            'data.pin' => 'required|string',
            'data.scan' => 'required|string',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'type.required' => 'Type field is required',
            'cloud_id.required' => 'Cloud ID is required',
            'data.pin.required' => 'PIN data is required',
            'data.scan.required' => 'Scan time data is required',
        ];
    }

    /**
     * Get the fingerprint ID from the request
     */
    public function getFingerprintId(): string
    {
        return $this->input('data.pin');
    }

    /**
     * Get the cloud ID from the request
     */
    public function getCloudId(): string
    {
        return $this->input('cloud_id');
    }

    /**
     * Get the scan time from the request
     */
    public function getScanTime(): string
    {
        return $this->input('data.scan');
    }
}
