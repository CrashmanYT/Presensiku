<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use Filament\Forms;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;

class Settings extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationGroup = 'Pengaturan Sistem';

    protected static string $view = 'filament.pages.settings';

    protected static ?string $title = 'Pengaturan Sistem';

    protected static ?string $navigationLabel = 'Pengaturan Sistem';

    protected static ?int $navigationSort = 6;

    public ?array $data = [];

    public function mount(): void
    {
        // Fill the form with nested, already-casted settings from the model helper.
        $this->form->fill(Setting::allAsNested());
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Tabs::make('Pengaturan')
                    ->tabs([
                        Forms\Components\Tabs\Tab::make('Pengaturan Dashboard')
                            ->schema([
                                Forms\Components\Section::make('Pengaturan Dashboard')
                                    ->description('Konfigurasi untuk realtime attendance dashboard.')
                                    ->icon('heroicon-o-computer-desktop')
                                    ->schema([
                                        Forms\Components\Toggle::make('dashboard.buttons.show_test')
                                            ->label('Tampilkan Tombol Tes')
                                            ->default(true)
                                            ->helperText('Jika aktif, tombol untuk mengetes scan akan muncul di dashboard absensi realtime.'),
                                    ]),
                            ]),

                        Forms\Components\Tabs\Tab::make('Pengaturan Absensi')
                            ->schema([
                                Forms\Components\Section::make('Pengaturan Absensi')
                                    ->description('Konfigurasi default untuk jadwal dan status absensi.')
                                    ->icon('heroicon-o-finger-print')
                                    ->schema([
                                        Forms\Components\Grid::make(2)
                                            ->schema([
                                                Forms\Components\TimePicker::make('attendance.defaults.time_in_start')
                                                    ->label('Default Jam Masuk Mulai')
                                                    ->default('07:00'),
                                                Forms\Components\TimePicker::make('attendance.defaults.time_in_end')
                                                    ->label('Default Jam Masuk Selesai')
                                                    ->default('08:00'),
                                                Forms\Components\TimePicker::make('attendance.defaults.time_out_start')
                                                    ->label('Default Jam Pulang Mulai')
                                                    ->default('15:00'),
                                                Forms\Components\TimePicker::make('attendance.defaults.time_out_end')
                                                    ->label('Default Jam Pulang Selesai')
                                                    ->default('16:00'),
                                            ]),
                                    ]),
                            ]),

                        Forms\Components\Tabs\Tab::make('Pengaturan Skor Disiplin')
                            ->schema([
                                Forms\Components\Section::make('Pengaturan Skor Disiplin')
                                    ->description('Atur poin untuk setiap status kehadiran yang akan memengaruhi peringkat disiplin siswa.')
                                    ->icon('heroicon-o-scale')
                                    ->schema([
                                        Forms\Components\Grid::make(2)
                                            ->schema([
                                                Forms\Components\TextInput::make('discipline.scores.hadir')
                                                    ->label('Poin Hadir')
                                                    ->numeric()
                                                    ->default(5)
                                                    ->helperText('Poin diberikan untuk setiap kehadiran.'),
                                                Forms\Components\TextInput::make('discipline.scores.terlambat')
                                                    ->label('Poin Terlambat')
                                                    ->numeric()
                                                    ->default(-2)
                                                    ->helperText('Poin dikurangi untuk setiap keterlambatan.'),
                                                Forms\Components\TextInput::make('discipline.scores.izin')
                                                    ->label('Poin Izin')
                                                    ->numeric()
                                                    ->default(0)
                                                    ->helperText('Poin untuk status izin.'),
                                                Forms\Components\TextInput::make('discipline.scores.sakit')
                                                    ->label('Poin Sakit')
                                                    ->numeric()
                                                    ->default(0)
                                                    ->helperText('Poin untuk status sakit.'),
                                                Forms\Components\TextInput::make('discipline.scores.tidak_hadir')
                                                    ->label('Poin Alpa (Tidak Hadir)')
                                                    ->numeric()
                                                    ->default(-5)
                                                    ->helperText('Poin dikurangi untuk setiap ketidakhadiran tanpa keterangan.'),
                                            ]),
                                    ]),
                            ]),

                        Forms\Components\Tabs\Tab::make('Pengaturan Notifikasi')
                            ->schema([
                                Forms\Components\Section::make('Pengaturan Notifikasi')
                                    ->description('Konfigurasi sistem notifikasi, termasuk WhatsApp.')
                                    ->icon('heroicon-o-bell')
                                    ->schema([
                                        Forms\Components\Toggle::make('notifications.enabled')
                                            ->label('Aktifkan Notifikasi')
                                            ->default(true)
                                            ->helperText('Aktifkan atau nonaktifkan semua notifikasi sistem.'),
                                        Forms\Components\CheckboxList::make('notifications.channels')
                                            ->label('Channel Notifikasi Aktif')
                                            ->options([
                                                'whatsapp' => 'WhatsApp',
                                                'email' => 'Email (Segera Hadir)',
                                                'sms' => 'SMS (Segera Hadir)',
                                            ])
                                            ->default(['whatsapp'])
                                            ->helperText('Pilih channel notifikasi yang akan digunakan.'),
                                        Forms\Components\TimePicker::make('notifications.absent.notification_time')
                                            ->label('Waktu Notifikasi Tidak Hadir')
                                            ->default('09:00')
                                            ->helperText('Waktu untuk mengirim notifikasi otomatis jika siswa tidak hadir.'),

                                        Forms\Components\TextInput::make('notifications.whatsapp.student_affairs_number')
                                            ->label('Nomor WA Kesiswaan/BK')
                                            ->tel()
                                            ->placeholder('6281234567890')
                                            ->helperText('Nomor WhatsApp untuk menerima notifikasi internal terkait siswa.'),
                                        Forms\Components\TextInput::make('notifications.whatsapp.administration_number')
                                            ->label('Nomor WA Tata Usaha (TU)')
                                            ->tel()
                                            ->placeholder('6281234567890')
                                            ->helperText('Nomor WhatsApp untuk menerima notifikasi internal terkait guru.'),

                                        Forms\Components\Fieldset::make('Ringkasan Bulanan ke Kesiswaan')
                                            ->schema([
                                                Forms\Components\Toggle::make('notifications.whatsapp.monthly_summary.enabled')
                                                    ->label('Aktifkan Ringkasan Bulanan')
                                                    ->default(true)
                                                    ->helperText('Jika aktif, sistem akan mengirim ringkasan bulanan siswa ke nomor kesiswaan.'),
                                                Forms\Components\TimePicker::make('notifications.whatsapp.monthly_summary.send_time')
                                                    ->label('Waktu Kirim Otomatis (Tanggal 1)')
                                                    ->seconds(false)
                                                    ->default('07:30')
                                                    ->helperText('Format HH:MM. Sistem akan mengirim pada tanggal 1 setiap bulan di sekitar waktu ini.'),
                                                Forms\Components\Select::make('notifications.whatsapp.monthly_summary.output')
                                                    ->label('Format Ringkasan')
                                                    ->options([
                                                        'text' => 'Teks WhatsApp',
                                                        'pdf_link' => 'Tautan PDF',
                                                        'pdf_attachment' => 'Lampiran PDF',
                                                    ])
                                                    ->default('text')
                                                    ->helperText('Pilih format pengiriman ringkasan ke kesiswaan.'),
                                                Forms\Components\Grid::make(2)
                                                    ->schema([
                                                        Forms\Components\TextInput::make('notifications.whatsapp.monthly_summary.thresholds.min_total_late')
                                                            ->label('Min. Keterlambatan/Bulan')
                                                            ->numeric()
                                                            ->default(3)
                                                            ->helperText('Siswa dengan total terlambat per bulan >= nilai ini akan diikutkan.'),
                                                        Forms\Components\TextInput::make('notifications.whatsapp.monthly_summary.thresholds.min_total_absent')
                                                            ->label('Min. Alpa/Bulan')
                                                            ->numeric()
                                                            ->default(2)
                                                            ->helperText('Siswa dengan total tidak hadir (alpa) per bulan >= nilai ini akan diikutkan.'),
                                                        Forms\Components\TextInput::make('notifications.whatsapp.monthly_summary.thresholds.min_score')
                                                            ->label('Skor Minimum')
                                                            ->numeric()
                                                            ->default(-5)
                                                            ->helperText('Siswa dengan skor disiplin <= nilai ini akan diikutkan.'),
                                                        Forms\Components\TextInput::make('notifications.whatsapp.monthly_summary.limit')
                                                            ->label('Batas Jumlah Siswa')
                                                            ->numeric()
                                                            ->default(50)
                                                            ->helperText('Jumlah maksimum siswa yang akan dikirim dalam ringkasan (akan dipotong jika melebihi).'),
                                                    ]),

                                            ]),

                                        Forms\Components\Fieldset::make('Laporan Harian Keterlambatan Guru (TU)')
                                            ->schema([
                                                Forms\Components\Toggle::make('notifications.whatsapp.teacher_late_daily.enabled')
                                                    ->label('Aktifkan Laporan Harian Terlambat Guru')
                                                    ->default(true)
                                                    ->helperText('Jika aktif, sistem akan mengirim ringkasan guru terlambat ke nomor TU setiap hari.'),
                                                Forms\Components\TimePicker::make('notifications.whatsapp.teacher_late_daily.send_time')
                                                    ->label('Waktu Kirim Otomatis')
                                                    ->seconds(false)
                                                    ->default('08:00')
                                                    ->helperText('Format HH:MM. Disarankan mengikuti akhir jendela jam masuk.'),
                                                Forms\Components\Select::make('notifications.whatsapp.teacher_late_daily.output')
                                                    ->label('Format Pengiriman')
                                                    ->options([
                                                        'pdf_link' => 'Tautan PDF',
                                                        'pdf_attachment' => 'Lampiran PDF',
                                                    ])
                                                    ->default('pdf_link')
                                                    ->helperText('Pastikan storage publik sudah di-link (php artisan storage:link) agar tautan PDF bisa diakses.'),
                                            ]),
                                    ]),
                            ]),

                        Forms\Components\Tabs\Tab::make('Pengaturan Sistem')
                            ->schema([
                                Forms\Components\Section::make('Pengaturan Sistem')
                                    ->description('Konfigurasi umum sistem dan lokalisasi.')
                                    ->icon('heroicon-o-cog-6-tooth')
                                    ->schema([
                                        Forms\Components\Grid::make(2)
                                            ->schema([
                                                Forms\Components\Select::make('system.localization.timezone')
                                                    ->label('Zona Waktu')
                                                    ->options([
                                                        'Asia/Jakarta' => 'WIB (Asia/Jakarta)',
                                                        'Asia/Makassar' => 'WITA (Asia/Makassar)',
                                                        'Asia/Jayapura' => 'WIT (Asia/Jayapura)',
                                                    ])
                                                    ->default('Asia/Jakarta'),
                                                Forms\Components\Select::make('system.localization.date_format')
                                                    ->label('Format Tanggal')
                                                    ->options([
                                                        'd/m/Y' => 'DD/MM/YYYY (25/07/2025)',
                                                        'Y-m-d' => 'YYYY-MM-DD (2025-07-25)',
                                                        'd-m-Y' => 'DD-MM-YYYY (25-07-2025)',
                                                        'M d, Y' => 'MMM DD, YYYY (Jul 25, 2025)',
                                                    ])
                                                    ->default('d/m/Y'),
                                                Forms\Components\Select::make('system.localization.language')
                                                    ->label('Bahasa Default')
                                                    ->options([
                                                        'id' => 'Bahasa Indonesia',
                                                        'en' => 'English',
                                                    ])
                                                    ->default('id'),
                                                Forms\Components\Toggle::make('system.maintenance_mode')
                                                    ->label('Mode Perawatan')
                                                    ->default(false)
                                                    ->helperText('Aktifkan mode perawatan untuk menonaktifkan akses publik.'),
                                            ]),
                                    ]),
                            ]),

                        Forms\Components\Tabs\Tab::make('Template Pesan WhatsApp')
                            ->schema([
                                Forms\Components\Section::make('Template Pesan WhatsApp')
                                    ->description('Kustomisasi template pesan notifikasi. Sistem akan memilih salah satu template secara acak.')
                                    ->icon('heroicon-o-chat-bubble-left-ellipsis')
                                    ->schema([
                                        Forms\Components\Tabs::make('Kategori Template WA')
                                            ->tabs([
                                                Forms\Components\Tabs\Tab::make('Pesan Siswa')
                                                    ->schema([
                                                        self::variantHelpPlaceholder('help_siswa', 'Tersedia: {nama_siswa}, {kelas}, {tanggal}, {jam_masuk}, {jam_seharusnya}'),

                                                        self::templateRepeater(
                                                            'notifications.whatsapp.templates.late',
                                                            'Pesan Keterlambatan',
                                                            'Variabel: {nama_siswa}, {jam_masuk}, {jam_seharusnya}, {kelas}',
                                                            3,
                                                            'Tambah Template Terlambat'
                                                        ),

                                                        self::templateRepeater(
                                                            'notifications.whatsapp.templates.absent',
                                                            'Pesan Tidak Hadir (Alpa)',
                                                            'Variabel: {nama_siswa}, {tanggal}, {kelas}',
                                                            3,
                                                            'Tambah Template Tidak Hadir'
                                                        ),

                                                        self::templateRepeater(
                                                            'notifications.whatsapp.templates.permit',
                                                            'Pesan Izin',
                                                            'Variabel: {nama_siswa}, {jam_masuk}, {kelas}',
                                                            3,
                                                            'Tambah Template Izin'
                                                        ),
                                                    ]),
                                                Forms\Components\Tabs\Tab::make('Internal (TU)')
                                                    ->schema([
                                                        self::variantHelpPlaceholder('help_tu', 'Tersedia: {date_title}, {pdf_url}, {list}'),

                                                        self::templateRepeater(
                                                            'notifications.whatsapp.templates.report_teacher_late_no_data',
                                                            'Tidak Ada Data (No Data)',
                                                            'Variabel: {date_title}',
                                                            3,
                                                            'Tambah Template (No Data)'
                                                        ),

                                                        self::templateRepeater(
                                                            'notifications.whatsapp.templates.report_teacher_late_pdf_link',
                                                            'Tautan PDF',
                                                            'Variabel: {date_title}, {pdf_url}',
                                                            3,
                                                            'Tambah Template (Tautan PDF)'
                                                        ),

                                                        self::templateRepeater(
                                                            'notifications.whatsapp.templates.report_teacher_late_pdf_attachment_caption',
                                                            'Caption Lampiran PDF',
                                                            'Variabel: {date_title}',
                                                            2,
                                                            'Tambah Template (Caption Lampiran)'
                                                        ),

                                                        self::templateRepeater(
                                                            'notifications.whatsapp.templates.report_teacher_late_text',
                                                            'Caption Teks (Daftar)',
                                                            'Variabel: {date_title}, {list}',
                                                            6,
                                                            'Tambah Template (Caption Teks)'
                                                        ),
                                                    ]),

                                                Forms\Components\Tabs\Tab::make('Internal (Kesiswaan)')
                                                    ->schema([
                                                        self::variantHelpPlaceholder('help_kesiswaan', 'Tersedia: {month_title}, {pdf_url}, {list}'),

                                                        self::templateRepeater(
                                                            'notifications.whatsapp.templates.monthly_summary_no_data',
                                                            'Tidak Ada Data (No Data)',
                                                            'Variabel: {month_title}',
                                                            3,
                                                            'Tambah Template (No Data)'
                                                        ),

                                                        self::templateRepeater(
                                                            'notifications.whatsapp.templates.monthly_summary_pdf_link',
                                                            'Tautan PDF',
                                                            'Variabel: {month_title}, {pdf_url}',
                                                            3,
                                                            'Tambah Template (Tautan PDF)'
                                                        ),

                                                        self::templateRepeater(
                                                            'notifications.whatsapp.templates.monthly_summary_pdf_attachment_caption',
                                                            'Caption Lampiran PDF',
                                                            'Variabel: {month_title}',
                                                            2,
                                                            'Tambah Template (Caption Lampiran)'
                                                        ),

                                                        self::templateRepeater(
                                                            'notifications.whatsapp.templates.monthly_summary_text',
                                                            'Caption Teks (Daftar)',
                                                            'Variabel: {month_title}, {list}',
                                                            6,
                                                            'Tambah Template (Caption Teks)'
                                                        ),
                                                    ]),
                                            ])
                                            ->columnSpanFull(),
                                    ]),
                            ]),
                        Forms\Components\Tabs\Tab::make('Kamus Frasa')
                            ->schema([
                                Forms\Components\Section::make('Kamus Frasa')
                                    ->description('Kelola grup frasa acak untuk digunakan di template WA. Gunakan macro {v:key} di dalam template untuk memilih salah satu frasa secara acak dari grup tersebut.')
                                    ->icon('heroicon-o-book-open')
                                    ->schema([
                                        Forms\Components\Placeholder::make('help_kamus_frasa')
                                            ->label('Cara Pakai')
                                            ->content(new HtmlString(
                                                '<div class="rounded-md border border-gray-200 bg-gray-50 p-3 text-gray-800 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100 overflow-x-auto">'
                                                . '<div class="flex items-center gap-2 mb-2 text-[11px] font-medium text-gray-600 dark:text-gray-300">'
                                                . '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="h-4 w-4"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 8.75 5.75 12l2.5 3.25m7.5-6.5 2.5 3.25-2.5 3.25M13 4.75 11 19.25"/></svg>'
                                                . '<span>Helper</span>'
                                                . '</div>'
                                                . '<pre class="font-mono text-xs whitespace-pre">'
                                                . e('Di template, tulis {v:key}. Contoh: "{v:salam}, Bapak/Ibu" akan memilih acak dari grup dengan key "salam".')
                                                . '</pre>'
                                                . '</div>'
                                            ))
                                            ->extraAttributes(['class' => 'mb-4'])
                                            ->columnSpanFull(),

                                        Forms\Components\Repeater::make('notifications.whatsapp.template_variants')
                                            ->label('Grup Frasa')
                                            ->itemLabel(fn(array $state) => ($state['key'] ?? null) ? ('Grup: ' . $state['key']) : 'Grup Frasa')
                                            ->reorderableWithButtons()
                                            ->collapsible()
                                            ->cloneable()
                                            ->deleteAction(fn(Action $action) => $action->requiresConfirmation())
                                            ->addActionLabel('Tambah Grup Frasa')
                                            ->schema([
                                                Forms\Components\TextInput::make('key')
                                                    ->label('Key (slug)')
                                                    ->required()
                                                    ->rule('regex:/^[a-z0-9_\.-]+$/')
                                                    ->helperText('Gunakan huruf kecil, angka, underscore (_), titik (.) atau minus (-). Contoh: salam, penutup.ramah')
                                                    ->extraAttributes(['class' => 'font-mono text-sm'])
                                                    ->maxLength(100),
                                                Forms\Components\TextInput::make('name')
                                                    ->label('Nama (opsional)')
                                                    ->maxLength(100),
                                                Forms\Components\Textarea::make('phrases')
                                                    ->label('Daftar Frasa (satu per baris)')
                                                    ->rows(6)
                                                    ->placeholder("Halo\nSelamat pagi\nAssalamualaikum")
                                                    ->helperText('Satu frasa per baris. Saat {v:key} digunakan, sistem akan memilih salah satu secara acak.')
                                                    ->extraAttributes(['class' => 'font-mono text-sm']),
                                            ]),
                                    ]),
                            ]),
                    ])
                    ->columnSpanFull(),
            ])
            ->statePath('data');
    }

    // Helpers to keep the form schema clean and DRY
    protected static function composeVariantHelp(string $base): string
    {
        $raw = Setting::get('notifications.whatsapp.template_variants', []);
        $keys = [];
        if (is_array($raw)) {
            if (array_keys($raw) !== range(0, count($raw) - 1)) {
                $keys = array_keys($raw);
            } else {
                foreach ($raw as $g) {
                    if (is_array($g) && !empty($g['key'])) {
                        $keys[] = (string) $g['key'];
                    }
                }
            }
        }
        $keys = array_values(array_unique(array_filter(array_map('strval', $keys))));
        if (!empty($keys)) {
            $sample = array_slice($keys, 0, 10);
            $more = count($keys) - count($sample);
            $list = implode(', ', $sample) . ($more > 0 ? ' +' . $more . ' lagi' : '');
            return $base . "\n" . 'Macro Frasa: gunakan {v:key}. Kunci: ' . $list . '.';
        }
        return $base . "\n" . 'Macro Frasa: gunakan {v:key}. Kelola di tab Kamus Frasa.';
    }

    protected static function variantHelpPlaceholder(string $name, string $base)
    {
        return Forms\Components\Placeholder::make($name)
            ->label('Bantuan Variabel')
            ->content(fn () => new HtmlString(
                '<div class="rounded-md border border-gray-200 bg-gray-50 p-3 text-gray-800 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100 overflow-x-auto">'
                . '<div class="flex items-center gap-2 mb-2 text-[11px] font-medium text-gray-600 dark:text-gray-300">'
                . '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="h-4 w-4"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 8.75 5.75 12l2.5 3.25m7.5-6.5 2.5 3.25-2.5 3.25M13 4.75 11 19.25"/></svg>'
                . '<span>Helper</span>'
                . '</div>'
                . '<pre class="font-mono text-xs whitespace-pre">'
                . e(self::composeVariantHelp($base))
                . '</pre>'
                . '</div>'
            ))
            ->extraAttributes(['class' => 'mb-4'])
            ->columnSpanFull();
    }

    protected static function extractTokens(string $variablesText): array
    {
        if ($variablesText === '') {
            return [];
        }
        if (!preg_match_all('/\{[a-zA-Z0-9_.]+\}/', $variablesText, $m)) {
            return [];
        }
        $tokens = $m[0] ?? [];
        $seen = [];
        $out = [];
        foreach ($tokens as $t) {
            if (!isset($seen[$t])) {
                $seen[$t] = true;
                $out[] = $t;
            }
        }
        return $out;
    }

    protected static function getVariantKeys(): array
    {
        $raw = Setting::get('notifications.whatsapp.template_variants', []);
        $keys = [];
        if (is_array($raw)) {
            if (array_keys($raw) !== range(0, count($raw) - 1)) {
                // Associative: keys are the variant group keys
                $keys = array_keys($raw);
            } else {
                // Repeater-like
                foreach ($raw as $g) {
                    if (is_array($g) && !empty($g['key'])) {
                        $keys[] = (string) $g['key'];
                    }
                }
            }
        }
        $keys = array_values(array_unique(array_filter(array_map('strval', $keys))));
        return array_slice($keys, 0, 30);
    }

    protected static function templateRepeater(
        string $path,
        string $label,
        string $variablesText,
        int $rows = 3,
        string $addActionLabel = 'Tambah Template'
    ) {
        return Forms\Components\Repeater::make($path)
            ->label($label)
            ->itemLabel(function (array $state) {
                $label = trim((string)($state['label'] ?? ''));
                if ($label !== '') {
                    return $label;
                }
                $message = (string)($state['message'] ?? '');
                $firstLine = trim((string) strtok($message, "\n"));
                if ($firstLine === '') {
                    return 'Template';
                }
                if (function_exists('mb_strimwidth')) {
                    return mb_strimwidth($firstLine, 0, 40, '…');
                }
                return strlen($firstLine) > 40 ? substr($firstLine, 0, 37) . '...' : $firstLine;
            })
            ->reorderableWithButtons()
            ->collapsible()
            ->collapsed()
            ->cloneable()
            ->deleteAction(fn (Action $action) => $action->requiresConfirmation())
            ->addActionLabel($addActionLabel)
            ->helperText(function (\Filament\Forms\Get $get) use ($path) {
                $items = $get($path) ?? [];
                $count = is_array($items) ? count($items) : 0;
                return new HtmlString('<span class="inline-flex items-center gap-1 text-xs text-gray-600 dark:text-gray-300">Jumlah template: <span class="px-1.5 py-0.5 rounded bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200">' . $count . '</span></span>');
            })
            ->schema([
                Forms\Components\TextInput::make('label')
                    ->label('Judul (opsional)')
                    ->maxLength(100),
                Forms\Components\Textarea::make('message')
                    ->label('Isi Pesan')
                    ->rows($rows)
                    ->extraAttributes(['class' => 'font-mono text-sm', 'data-message-field' => '1'])
                    ->helperText(function () use ($variablesText) {
                        $tokens = self::extractTokens($variablesText);
                        $variantKeys = self::getVariantKeys();

                        $chipsHtml = '';
                        foreach ($tokens as $t) {
                            $et = e($t);
                            $chipsHtml .= '<button type="button" class="px-2 py-0.5 text-xs rounded border border-gray-300 bg-white hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-900 dark:hover:bg-gray-800" @click.prevent="insert(\'' . $et . '\')">' . $et . '</button>';
                        }
                        if (!empty($variantKeys)) {
                            foreach ($variantKeys as $vk) {
                                $ev = e((string) $vk);
                                $label = '{v:' . $ev . '}';
                                $chipsHtml .= '<button type="button" class="px-2 py-0.5 text-xs rounded border border-gray-300 bg-white hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-900 dark:hover:bg-gray-800" @click.prevent="insert(\'' . $label . '\')">' . e($label) . '</button>';
                            }
                        }

                        $helperBox = '<div class="rounded-md border border-gray-200 bg-gray-50 p-2 text-gray-800 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100 overflow-x-auto mt-2">'
                            . '<div class="flex items-center gap-2 mb-1 text-[11px] font-medium text-gray-600 dark:text-gray-300">'
                            . '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="h-4 w-4"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 8.75 5.75 12l2.5 3.25m7.5-6.5 2.5 3.25-2.5 3.25M13 4.75 11 19.25"/></svg>'
                            . '<span>Helper</span>'
                            . '</div>'
                            . '<pre class="font-mono text-xs whitespace-pre">' . e($variablesText) . '</pre>'
                            . '</div>';

                        $chipsBox = <<<'HTML'
<div class="mt-2 flex flex-wrap gap-2" x-data="{ insert(token) { const scope = $el.closest('.fi-fo-field'); const ta = scope ? scope.querySelector('textarea[data-message-field=\'1\']') : null; if (!ta) return; const start = (typeof ta.selectionStart === 'number') ? ta.selectionStart : ta.value.length; const end = (typeof ta.selectionEnd === 'number') ? ta.selectionEnd : ta.value.length; const before = ta.value.slice(0, start); const after = ta.value.slice(end); ta.value = before + token + after; const pos = before.length + token.length; if (ta.setSelectionRange) ta.setSelectionRange(pos, pos); ta.dispatchEvent(new Event('input', { bubbles: true })); ta.dispatchEvent(new Event('change', { bubbles: true })); ta.focus(); } }">
  <div class="w-full text-[11px] text-gray-600 dark:text-gray-300">Klik untuk menyisipkan:</div>
HTML;
                        $chipsBox .= $chipsHtml;
                        $chipsBox .= '</div>';

                        return new HtmlString($helperBox . $chipsBox);
                    }),
            ]);
    }

    public function submit(): void
    {
        // Get current nested form state and flatten it.
        $data = $this->form->getState();
        $flattenedData = \Illuminate\Support\Arr::dot($data);

        // Persist atomically; type and group will be auto-resolved in the model.
        DB::transaction(function () use ($flattenedData) {
            foreach ($flattenedData as $key => $value) {
                Setting::set($key, $value);
            }
        });

        Notification::make()
            ->title('Pengaturan berhasil disimpan!')
            ->body('Semua konfigurasi telah diperbarui.')
            ->success()
            ->send();
    }
}
