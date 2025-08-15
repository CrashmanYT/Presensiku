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

        // Expand variant macros first, then interpolate variables.
        $template = $this->expandVariants($template);
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

    /**
     * Expand variant macros of the form {v:key} using phrase groups
     * defined in settings at `notifications.whatsapp.template_variants`.
     *
     * The settings format supported:
     * - An associative array: [key => ["phrase 1", "phrase 2", ...], ...]
     * - Or an array of groups: [["key" => "greet", "phrases" => "Hi\nHalo\nSelamat pagi", "name" => "Greet"], ...]
     *
     * Unknown keys or empty groups will be left as-is.
     */
    public function expandVariants(string $text): string
    {
        $groups = $this->getVariantGroups();
        if ($groups === []) {
            return $text;
        }

        return preg_replace_callback('/\{v:([A-Za-z0-9_\.-]+)\}/', function ($matches) use ($groups) {
            $key = $matches[1];
            $options = $groups[$key] ?? null;
            if (!is_array($options) || empty($options)) {
                // Keep as-is if not found
                return $matches[0];
            }
            // Filter to non-empty strings
            $options = array_values(array_filter(array_map(fn ($s) => trim((string) $s), $options), fn ($s) => $s !== ''));
            if (empty($options)) {
                return $matches[0];
            }
            return (string) $options[array_rand($options)];
        }, $text);
    }

    /**
     * Normalize and return phrase variant groups as [key => [phrases...]].
     * Accepts either associative mapping or an array of group objects.
     * @return array<string, string[]>
     */
    private function getVariantGroups(): array
    {
        $raw = $this->settings->get('notifications.whatsapp.template_variants', []);
        if (!is_array($raw)) {
            return [];
        }

        // Case 1: associative array of key => array|string
        $isAssoc = static function (array $arr): bool {
            return array_keys($arr) !== range(0, count($arr) - 1);
        };

        $result = [];

        if ($isAssoc($raw)) {
            foreach ($raw as $key => $val) {
                if (!is_string($key) || $key === '') {
                    continue;
                }
                $phrases = [];
                if (is_array($val)) {
                    $phrases = $val;
                } elseif (is_string($val)) {
                    // Support newline-separated string stored as value
                    $phrases = preg_split("/\r?\n/", $val) ?: [];
                }
                $phrases = array_values(array_filter(array_map(fn ($s) => trim((string) $s), $phrases), fn ($s) => $s !== ''));
                if (!empty($phrases)) {
                    $result[$key] = $phrases;
                }
            }
            return $result;
        }

        // Case 2: array of group objects: [{ key, phrases, name? }]
        foreach ($raw as $group) {
            if (!is_array($group)) {
                continue;
            }
            $key = (string) ($group['key'] ?? '');
            if ($key === '') {
                continue;
            }
            $phrasesField = $group['phrases'] ?? [];
            if (is_string($phrasesField)) {
                $phrases = preg_split("/\r?\n/", $phrasesField) ?: [];
            } elseif (is_array($phrasesField)) {
                $phrases = $phrasesField;
            } else {
                $phrases = [];
            }
            $phrases = array_values(array_filter(array_map(fn ($s) => trim((string) $s), $phrases), fn ($s) => $s !== ''));
            if (!empty($phrases)) {
                $result[$key] = $phrases;
            }
        }

        return $result;
    }
}
