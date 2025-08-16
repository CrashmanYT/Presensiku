@php
    try {
        /** @var \App\Contracts\SettingsRepositoryInterface $settings */
        $settings = app(\App\Contracts\SettingsRepositoryInterface::class);
        $raw = $settings->get('notifications.whatsapp.template_variants', []);
        $keys = [];
        if (is_array($raw)) {
            $isAssoc = array_keys($raw) !== range(0, count($raw) - 1);
            if ($isAssoc) {
                $keys = array_keys($raw);
            } else {
                foreach ($raw as $group) {
                    if (is_array($group) && !empty($group['key'])) {
                        $keys[] = (string) $group['key'];
                    }
                }
            }
        }
        sort($keys);
    } catch (Throwable $e) {
        $keys = [];
    }
@endphp

<div class="rounded-md border border-gray-200 bg-gray-50 p-3 font-mono text-xs text-gray-700 space-y-2">
    <div><strong>Placeholder:</strong> {student_name}, {date}, {time_in}, {status_label}</div>
    <div>
        <strong>Varian frasa {v:key}:</strong>
        @if(count($keys))
            {{ implode(', ', array_map(fn($k) => '{v:' . $k . '}', $keys)) }}
        @else
            <em>Tidak ada varian terdaftar. Tambahkan di Settings » WhatsApp » Kamus Frasa.</em>
        @endif
    </div>
    <div>
        <strong>Contoh:</strong> {v:greet} Orang Tua/Wali {student_name}, Info kehadiran {date}: {status_label}. Jam Masuk: {time_in}. {v:closing}
    </div>
</div>
