<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Request validator for attendance webhook payloads from fingerprint devices.
 *
 * Expected payload example:
 * {
 *   "type": "scan",
 *   "cloud_id": "device-cloud-id",
 *   "data": {
 *     "pin": "00123",
 *     "scan": "2025-08-12 07:15:30"
 *   }
 * }
 */
class WebhookAttendanceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * Webhook endpoint does not require user auth.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true; // Webhook endpoint, no user authorization needed
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
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
     *
     * @return array<string,string>
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
     *
     * @return string
     */
    public function getFingerprintId(): string
    {
        return $this->input('data.pin');
    }

    /**
     * Get the cloud ID from the request
     *
     * @return string
     */
    public function getCloudId(): string
    {
        return $this->input('cloud_id');
    }

    /**
     * Get the scan time from the request
     *
     * @return string ISO-like datetime string from device
     */
    public function getScanTime(): string
    {
        return $this->input('data.scan');
    }
}
