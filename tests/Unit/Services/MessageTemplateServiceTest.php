<?php

namespace Tests\Unit\Services;

use App\Contracts\SettingsRepositoryInterface;
use App\Services\MessageTemplateService;
use Illuminate\Support\Facades\Log;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MessageTemplateServiceTest extends TestCase
{
    private function makeServiceWithSettings(array $map): MessageTemplateService
    {
        $settings = $this->createMock(SettingsRepositoryInterface::class);
        $settings->method('get')->willReturnMap($map);
        return new MessageTemplateService($settings);
    }

    #[Test]
    public function interpolate_replaces_known_placeholders_and_leaves_unknown(): void
    {
        $svc = $this->makeServiceWithSettings([]);
        $tpl = 'Halo {name}, kelas {kelas}. Unknown: {unknown}';
        $out = $svc->interpolate($tpl, [
            'name' => 'Budi',
            'kelas' => '7A',
        ]);

        $this->assertSame('Halo Budi, kelas 7A. Unknown: {unknown}', $out);
    }

    #[Test]
    public function expand_variants_with_associative_map(): void
    {
        $map = [
            ['notifications.whatsapp.template_variants', [], [
                'greet' => ['Halo', 'Hi'],
            ]],
        ];
        $svc = $this->makeServiceWithSettings($map);
        $result = $svc->expandVariants('Test {v:greet}!');
        $this->assertTrue(in_array($result, ['Test Halo!', 'Test Hi!'], true));

        // Unknown key should be left as-is
        $this->assertSame('X {v:unknown} Y', $svc->expandVariants('X {v:unknown} Y'));
    }

    #[Test]
    public function expand_variants_with_group_array(): void
    {
        $map = [
            ['notifications.whatsapp.template_variants', [], [
                ['key' => 'salam', 'phrases' => "Halo\nHi\nSelamat pagi", 'name' => 'Salam'],
            ]],
        ];
        $svc = $this->makeServiceWithSettings($map);
        $result = $svc->expandVariants('Test {v:salam}!');
        $this->assertTrue(in_array($result, ['Test Halo!', 'Test Hi!', 'Test Selamat pagi!'], true));
    }

    #[Test]
    public function render_by_type_expands_variants_then_interpolates_variables(): void
    {
        $templates = [
            'late' => [
                ['label' => 'default', 'message' => 'Halo {v:greet}, {name}!'],
            ],
        ];
        $map = [
            ['notifications.whatsapp.template_variants', [], [
                'greet' => ['Pak', 'Bu'],
            ]],
            ['notifications.whatsapp.templates', [], $templates],
        ];
        $svc = $this->makeServiceWithSettings($map);
        $out = $svc->renderByType('late', ['name' => 'Budi']);
        $this->assertTrue(in_array($out, ['Halo Pak, Budi!', 'Halo Bu, Budi!'], true));
    }

    #[Test]
    public function render_by_type_returns_empty_string_when_bucket_missing_and_logs(): void
    {
        Log::shouldReceive('warning')->once();
        $map = [
            ['notifications.whatsapp.templates', [], []],
        ];
        $svc = $this->makeServiceWithSettings($map);
        $this->assertSame('', $svc->renderByType('absent', ['name' => 'Budi']));
    }

    #[Test]
    public function render_by_type_returns_empty_string_when_selected_template_empty_and_logs(): void
    {
        Log::shouldReceive('warning')->once();
        $templates = [
            'late' => [
                ['label' => 'broken', 'message' => ''],
            ],
        ];
        $map = [
            ['notifications.whatsapp.templates', [], $templates],
        ];
        $svc = $this->makeServiceWithSettings($map);
        $this->assertSame('', $svc->renderByType('late', ['name' => 'Budi']));
    }

    // --- Specific tests requested ---

    #[Test]
    public function random_template_is_selected_across_runs(): void
    {
        $templates = [
            'late' => [
                ['label' => 'A', 'message' => 'A-{name}'],
                ['label' => 'B', 'message' => 'B-{name}'],
            ],
        ];
        $map = [
            ['notifications.whatsapp.template_variants', [], []],
            ['notifications.whatsapp.templates', [], $templates],
        ];
        $svc = $this->makeServiceWithSettings($map);

        $seen = [];
        for ($i = 0; $i < 200; $i++) {
            $out = $svc->renderByType('late', ['name' => 'Budi']);
            $seen[$out] = true;
            if (count($seen) === 2) {
                break;
            }
        }

        // With high probability both templates should appear at least once
        $this->assertCount(2, $seen);
        $this->assertArrayHasKey('A-Budi', $seen);
        $this->assertArrayHasKey('B-Budi', $seen);
    }

    #[Test]
    public function phrase_variant_randomness_across_runs(): void
    {
        $templates = [
            'late' => [
                ['label' => 'default', 'message' => 'Halo {v:greet}, {name}!'],
            ],
        ];
        $map = [
            ['notifications.whatsapp.template_variants', [], [
                'greet' => ['Pak', 'Bu'],
            ]],
            ['notifications.whatsapp.templates', [], $templates],
        ];
        $svc = $this->makeServiceWithSettings($map);

        $seen = [];
        for ($i = 0; $i < 200; $i++) {
            $out = $svc->renderByType('late', ['name' => 'Budi']);
            $seen[$out] = true;
            if (count($seen) === 2) {
                break;
            }
        }

        $this->assertCount(2, $seen);
        $this->assertArrayHasKey('Halo Pak, Budi!', $seen);
        $this->assertArrayHasKey('Halo Bu, Budi!', $seen);
    }
}
