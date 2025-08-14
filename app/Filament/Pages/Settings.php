<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use Filament\Forms;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;

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
                                                        Forms\Components\Placeholder::make('help_siswa')
                                                            ->label('Bantuan Variabel')
                                                            ->content('Tersedia: {nama_siswa}, {kelas}, {tanggal}, {jam_masuk}, {jam_seharusnya}')
                                                            ->columnSpanFull(),
                                                        

                                                        Forms\Components\Repeater::make('notifications.whatsapp.templates.late')
                                                            ->label('Pesan Keterlambatan')
                                                            ->itemLabel(fn (array $state) => $state['label'] ?? 'Template')
                                                            ->reorderableWithButtons()
                                                            ->collapsible()
                                                            ->cloneable()
                                                            ->deleteAction(fn (Action $action) => $action->requiresConfirmation())
                                                            ->addActionLabel('Tambah Template Terlambat')
                                                            ->schema([
                                                                Forms\Components\TextInput::make('label')
                                                                    ->label('Judul (opsional)')
                                                                    ->maxLength(100),
                                                                Forms\Components\Textarea::make('message')
                                                                    ->label('Isi Pesan')
                                                                    ->rows(3)
                                                                    ->extraAttributes(['class' => 'font-mono text-sm'])
                                                                    ->helperText('Variabel: {nama_siswa}, {jam_masuk}, {jam_seharusnya}, {kelas}'),
                                                            ]),

                                                        Forms\Components\Repeater::make('notifications.whatsapp.templates.absent')
                                                            ->label('Pesan Tidak Hadir (Alpa)')
                                                            ->itemLabel(fn (array $state) => $state['label'] ?? 'Template')
                                                            ->reorderableWithButtons()
                                                            ->collapsible()
                                                            ->cloneable()
                                                            ->deleteAction(fn (Action $action) => $action->requiresConfirmation())
                                                            ->addActionLabel('Tambah Template Tidak Hadir')
                                                            ->schema([
                                                                Forms\Components\TextInput::make('label')
                                                                    ->label('Judul (opsional)')
                                                                    ->maxLength(100),
                                                                Forms\Components\Textarea::make('message')
                                                                    ->label('Isi Pesan')
                                                                    ->rows(3)
                                                                    ->extraAttributes(['class' => 'font-mono text-sm'])
                                                                    ->helperText('Variabel: {nama_siswa}, {tanggal}, {kelas}'),
                                                            ]),

                                                        Forms\Components\Repeater::make('notifications.whatsapp.templates.permit')
                                                            ->label('Pesan Izin')
                                                            ->itemLabel(fn (array $state) => $state['label'] ?? 'Template')
                                                            ->reorderableWithButtons()
                                                            ->collapsible()
                                                            ->cloneable()
                                                            ->deleteAction(fn (Action $action) => $action->requiresConfirmation())
                                                            ->addActionLabel('Tambah Template Izin')
                                                            ->schema([
                                                                Forms\Components\TextInput::make('label')
                                                                    ->label('Judul (opsional)')
                                                                    ->maxLength(100),
                                                                Forms\Components\Textarea::make('message')
                                                                    ->label('Isi Pesan')
                                                                    ->rows(3)
                                                                    ->extraAttributes(['class' => 'font-mono text-sm'])
                                                                    ->helperText('Variabel: {nama_siswa}, {jam_masuk}, {kelas}'),
                                                            ]),
                                                    ]),

                                                Forms\Components\Tabs\Tab::make('Internal (TU)')
                                                    ->schema([
                                                        Forms\Components\Placeholder::make('help_tu')
                                                            ->label('Bantuan Variabel')
                                                            ->content('Tersedia: {date_title}, {pdf_url}, {list}')
                                                            ->columnSpanFull(),
                                                        

                                                        Forms\Components\Repeater::make('notifications.whatsapp.templates.report_teacher_late_no_data')
                                                            ->label('Tidak Ada Data (No Data)')
                                                            ->itemLabel(fn (array $state) => $state['label'] ?? 'Template')
                                                            ->reorderableWithButtons()
                                                            ->collapsible()
                                                            ->cloneable()
                                                            ->deleteAction(fn (Action $action) => $action->requiresConfirmation())
                                                            ->addActionLabel('Tambah Template (No Data)')
                                                            ->schema([
                                                                Forms\Components\TextInput::make('label')
                                                                    ->label('Judul (opsional)')
                                                                    ->maxLength(100),
                                                                Forms\Components\Textarea::make('message')
                                                                    ->label('Isi Pesan')
                                                                    ->rows(3)
                                                                    ->extraAttributes(['class' => 'font-mono text-sm'])
                                                                    ->helperText('Variabel: {date_title}'),
                                                            ]),

                                                        Forms\Components\Repeater::make('notifications.whatsapp.templates.report_teacher_late_pdf_link')
                                                            ->label('Tautan PDF')
                                                            ->itemLabel(fn (array $state) => $state['label'] ?? 'Template')
                                                            ->reorderableWithButtons()
                                                            ->collapsible()
                                                            ->cloneable()
                                                            ->deleteAction(fn (Action $action) => $action->requiresConfirmation())
                                                            ->addActionLabel('Tambah Template (Tautan PDF)')
                                                            ->schema([
                                                                Forms\Components\TextInput::make('label')
                                                                    ->label('Judul (opsional)')
                                                                    ->maxLength(100),
                                                                Forms\Components\Textarea::make('message')
                                                                    ->label('Isi Pesan')
                                                                    ->rows(3)
                                                                    ->extraAttributes(['class' => 'font-mono text-sm'])
                                                                    ->helperText('Variabel: {date_title}, {pdf_url}'),
                                                            ]),

                                                        Forms\Components\Repeater::make('notifications.whatsapp.templates.report_teacher_late_pdf_attachment_caption')
                                                            ->label('Caption Lampiran PDF')
                                                            ->itemLabel(fn (array $state) => $state['label'] ?? 'Template')
                                                            ->reorderableWithButtons()
                                                            ->collapsible()
                                                            ->cloneable()
                                                            ->deleteAction(fn (Action $action) => $action->requiresConfirmation())
                                                            ->addActionLabel('Tambah Template (Caption Lampiran)')
                                                            ->schema([
                                                                Forms\Components\TextInput::make('label')
                                                                    ->label('Judul (opsional)')
                                                                    ->maxLength(100),
                                                                Forms\Components\Textarea::make('message')
                                                                    ->label('Isi Pesan')
                                                                    ->rows(2)
                                                                    ->extraAttributes(['class' => 'font-mono text-sm'])
                                                                    ->helperText('Variabel: {date_title}'),
                                                            ]),

                                                        Forms\Components\Repeater::make('notifications.whatsapp.templates.report_teacher_late_text')
                                                            ->label('Fallback Teks (Daftar)')
                                                            ->itemLabel(fn (array $state) => $state['label'] ?? 'Template')
                                                            ->reorderableWithButtons()
                                                            ->collapsible()
                                                            ->cloneable()
                                                            ->deleteAction(fn (Action $action) => $action->requiresConfirmation())
                                                            ->addActionLabel('Tambah Template (Fallback Teks)')
                                                            ->schema([
                                                                Forms\Components\TextInput::make('label')
                                                                    ->label('Judul (opsional)')
                                                                    ->maxLength(100),
                                                                Forms\Components\Textarea::make('message')
                                                                    ->label('Isi Pesan')
                                                                    ->rows(6)
                                                                    ->extraAttributes(['class' => 'font-mono text-sm'])
                                                                    ->helperText('Variabel: {date_title}, {list}'),
                                                            ]),
                                                    ]),

                                                Forms\Components\Tabs\Tab::make('Internal (Kesiswaan)')
                                                    ->schema([
                                                        Forms\Components\Placeholder::make('help_kesiswaan')
                                                            ->label('Bantuan Variabel')
                                                            ->content('Tersedia: {month_title}, {pdf_url}, {list}')
                                                            ->columnSpanFull(),

                                                        Forms\Components\Repeater::make('notifications.whatsapp.templates.monthly_summary_no_data')
                                                            ->label('Tidak Ada Data (No Data)')
                                                            ->itemLabel(fn (array $state) => $state['label'] ?? 'Template')
                                                            ->reorderableWithButtons()
                                                            ->collapsible()
                                                            ->cloneable()
                                                            ->deleteAction(fn (Action $action) => $action->requiresConfirmation())
                                                            ->addActionLabel('Tambah Template (No Data)')
                                                            ->schema([
                                                                Forms\Components\TextInput::make('label')
                                                                    ->label('Judul (opsional)')
                                                                    ->maxLength(100),
                                                                Forms\Components\Textarea::make('message')
                                                                    ->label('Isi Pesan')
                                                                    ->rows(3)
                                                                    ->extraAttributes(['class' => 'font-mono text-sm'])
                                                                    ->helperText('Variabel: {month_title}'),
                                                            ]),

                                                        Forms\Components\Repeater::make('notifications.whatsapp.templates.monthly_summary_pdf_link')
                                                            ->label('Tautan PDF')
                                                            ->itemLabel(fn (array $state) => $state['label'] ?? 'Template')
                                                            ->reorderableWithButtons()
                                                            ->collapsible()
                                                            ->cloneable()
                                                            ->deleteAction(fn (Action $action) => $action->requiresConfirmation())
                                                            ->addActionLabel('Tambah Template (Tautan PDF)')
                                                            ->schema([
                                                                Forms\Components\TextInput::make('label')
                                                                    ->label('Judul (opsional)')
                                                                    ->maxLength(100),
                                                                Forms\Components\Textarea::make('message')
                                                                    ->label('Isi Pesan')
                                                                    ->rows(3)
                                                                    ->extraAttributes(['class' => 'font-mono text-sm'])
                                                                    ->helperText('Variabel: {month_title}, {pdf_url}')
                                                            ]),

                                                        Forms\Components\Repeater::make('notifications.whatsapp.templates.monthly_summary_pdf_attachment_caption')
                                                            ->label('Caption Lampiran PDF')
                                                            ->itemLabel(fn (array $state) => $state['label'] ?? 'Template')
                                                            ->reorderableWithButtons()
                                                            ->collapsible()
                                                            ->cloneable()
                                                            ->deleteAction(fn (Action $action) => $action->requiresConfirmation())
                                                            ->addActionLabel('Tambah Template (Caption Lampiran)')
                                                            ->schema([
                                                                Forms\Components\TextInput::make('label')
                                                                    ->label('Judul (opsional)')
                                                                    ->maxLength(100),
                                                                Forms\Components\Textarea::make('message')
                                                                    ->label('Isi Pesan')
                                                                    ->rows(2)
                                                                    ->extraAttributes(['class' => 'font-mono text-sm'])
                                                                    ->helperText('Variabel: {month_title}')
                                                            ]),
                                                        
                                                        Forms\Components\Repeater::make('notifications.whatsapp.templates.monthly_summary_text')
                                                            ->label('Fallback Teks (Daftar)')
                                                            ->itemLabel(fn (array $state) => $state['label'] ?? 'Template')
                                                            ->reorderableWithButtons()
                                                            ->collapsible()
                                                            ->cloneable()
                                                            ->deleteAction(fn (Action $action) => $action->requiresConfirmation())
                                                            ->addActionLabel('Tambah Template (Fallback Teks)')
                                                            ->schema([
                                                                Forms\Components\TextInput::make('label')
                                                                    ->label('Judul (opsional)')
                                                                    ->maxLength(100),
                                                                Forms\Components\Textarea::make('message')
                                                                    ->label('Isi Pesan')
                                                                    ->rows(6)
                                                                    ->extraAttributes(['class' => 'font-mono text-sm'])
                                                                    ->helperText('Variabel: {month_title}, {list}')
                                                            ]),
                                                    ]),
                                            ])
                                            ->columnSpanFull(),
                                    ]),
                            ]),
                    ])
                    ->columnSpanFull(),
            ])
            ->statePath('data');
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
