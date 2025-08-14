<?php

namespace App\Services;

use App\Contracts\SettingsRepositoryInterface;
use Illuminate\Support\Facades\Log;

class MessageTemplateService
{
    public function __construct(
        private SettingsRepositoryInterface $settings,
    ) {}

    /**
     * Render a WhatsApp message by logical template type (e.g. late, absent, permit)
     * using templates from settings at notifications.whatsapp.templates.{type}.
     *
     * Placeholders use curly braces, e.g. {student_name}.
     * Unknown/missing placeholders will be left as-is.
     */
    public function renderByType(string $type, array $variables): string
    {
        $all = $this->settings->get('notifications.whatsapp.templates', []);
        if (!is_array($all)) {
            Log::warning('MessageTemplateService: templates root is not an array');
            return '';
        }
        $bucket = $all[$type] ?? null;
        if (!is_array($bucket) || empty($bucket)) {
            Log::warning("MessageTemplateService: template bucket not found or empty for type: {$type}");
            return '';
        }

        $random = $bucket[array_rand($bucket)] ?? [];
        $template = $random['message'] ?? '';
        if ($template === '') {
            Log::warning("MessageTemplateService: selected template empty for type: {$type}");
            return '';
        }

        return $this->interpolate($template, $variables);
    }

    /**
     * Basic interpolation replacing {key} with provided values.
     */
    public function interpolate(string $template, array $variables): string
    {
        foreach ($variables as $key => $value) {
            $template = str_replace('{' . $key . '}', (string) $value, $template);
        }
        return $template;
    }
}
