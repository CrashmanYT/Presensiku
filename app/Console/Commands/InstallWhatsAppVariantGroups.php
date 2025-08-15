<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Setting;

class InstallWhatsAppVariantGroups extends Command
{
    protected $signature = 'settings:install-wa-variants {--overwrite : Overwrite existing values if present}';

    protected $description = 'Install default phrase variant groups (Kamus Frasa) for WhatsApp templates.';

    public function handle(): int
    {
        $overwrite = (bool) $this->option('overwrite');
        $key = 'notifications.whatsapp.template_variants';

        if (!$overwrite && Setting::has($key)) {
            $this->line("Skip (exists): {$key}");
            $this->line('Use --overwrite to replace existing Kamus Frasa.');
            return self::SUCCESS;
        }

        // Default groups: simple, friendly Indonesian phrases
        $groups = [
            [
                'key' => 'salam',
                'name' => 'Salam Pembuka',
                'phrases' => "Halo\nSelamat pagi\nSelamat siang\nSelamat sore\nAssalamualaikum",
            ],
            [
                'key' => 'penutup.ramah',
                'name' => 'Penutup Ramah',
                'phrases' => "Terima kasih\nTerima kasih atas perhatiannya\nMohon kerjasamanya\nDemikian, terima kasih",
            ],
            [
                'key' => 'pengantar.daftar',
                'name' => 'Pengantar Daftar',
                'phrases' => "Berikut ringkasannya:\nBerikut daftar siswa:\nRincian sebagai berikut:",
            ],
            [
                'key' => 'lanjutan.tag',
                'name' => 'Penanda Lanjutan',
                'phrases' => "(lanjutan)\n(Lanjutan)",
            ],
        ];

        Setting::set($key, $groups, 'json', 'notifications');
        $this->info(($overwrite ? 'Updated' : 'Created') . ": {$key}");
        $this->info('Done installing default WhatsApp phrase variant groups.');

        return self::SUCCESS;
    }
}
