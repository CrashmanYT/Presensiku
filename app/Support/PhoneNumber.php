<?php

namespace App\Support;

class PhoneNumber
{
    /**
     * Normalize Indonesian MSISDN into 62XXXXXXXXXX digits only.
     * Accepts inputs with spaces, dashes, leading 0, leading +62, or bare 8xxxxxxxxx.
     * Returns null if invalid after normalization.
     */
    public static function normalize(?string $input): ?string
    {
        if (!$input) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $input);
        if ($digits === null || $digits === '') {
            return null;
        }

        // Handle common prefixes
        if (str_starts_with($digits, '0')) {
            $digits = '62' . substr($digits, 1);
        } elseif (str_starts_with($digits, '62')) {
            // already normalized
        } elseif (str_starts_with($digits, '8')) {
            $digits = '62' . $digits;
        }

        // Basic length validation
        if (strlen($digits) < 10) {
            return null;
        }

        return $digits;
    }
}
