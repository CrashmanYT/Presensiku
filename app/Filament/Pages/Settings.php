<?php

namespace App\Filament\Pages;

use Filament\Forms;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Form;
use Filament\Pages\Page;
use App\Models\Setting;
use App\Helpers\SettingsHelper;
use Filament\Notifications\Notification;
use Filament\Support\Enums\Alignment;
use Illuminate\Support\Facades\Auth;

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
        // 1. Get all settings from the database.
        $settings = Setting::all();
        $preparedData = [];

        // 2. Iterate and build a nested array.
        foreach ($settings as $setting) {
            $value = $setting->value;
            // Cast the value based on its type in the DB
            switch ($setting->type) {
                case 'json':
                    $value = json_decode($value, true) ?? [];
                    break;
                case 'boolean':
                    $value = filter_var($value, FILTER_VALIDATE_BOOLEAN);
                    break;
                case 'integer':
                    $value = (int) $value;
                    break;
            }
            // Use Arr::set to create the nested structure from the dot-notation key.
            \Illuminate\Support\Arr::set($preparedData, $setting->key, $value);
        }

        // 3. Fill the form with the correctly structured nested array.
        $this->form->fill($preparedData);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                // Dashboard Settings
                Forms\Components\Section::make('Pengaturan Dashboard')
                    ->description('Konfigurasi untuk realtime attendance dashboard.')
                    ->icon('heroicon-o-computer-desktop')
                    ->schema([
                        Forms\Components\Toggle::make('dashboard.buttons.show_test')
                            ->label('Tampilkan Tombol Tes')
                            ->default(true)
                            ->helperText('Jika aktif, tombol untuk mengetes scan akan muncul di dashboard absensi realtime.'),
                    ]),

                // Attendance Settings
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

                // Discipline Score Settings
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
                            ])
                    ]),

                // Notification Settings
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
                        Forms\Components\TextInput::make('notifications.whatsapp.api_key')
                            ->label('WhatsApp API Key')
                            ->password()
                            ->revealable()
                            ->helperText('Kunci API untuk layanan integrasi WhatsApp.'),
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
                    ]),

                // System Settings
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

                // WhatsApp Templates
                Forms\Components\Section::make('Template Pesan WhatsApp')
                    ->description('Kustomisasi template pesan notifikasi. Sistem akan memilih salah satu template secara acak.')
                    ->icon('heroicon-o-chat-bubble-left-ellipsis')
                    ->schema([
                        Forms\Components\Repeater::make('notifications.whatsapp.templates.late')
                            ->label('Pesan Keterlambatan')
                            ->reorderableWithButtons()
                            ->collapsible()
                            ->cloneable()
                            ->deleteAction(
                                fn (Action $action) => $action->requiresConfirmation(),
                            )
                            ->addActionLabel('Tambah Template Terlambat')
                            ->schema([
                                Forms\Components\Textarea::make('message')
                                    ->label('Isi Pesan')
                                    ->rows(3)
                                    ->default('Yth. Bapak/Ibu, ananda {nama_siswa} tercatat terlambat hari ini. Jam masuk: {jam_masuk}, seharusnya: {jam_seharusnya}.')
                                    ->helperText('Variabel: {nama_siswa}, {jam_masuk}, {jam_seharusnya}, {kelas}'),
                            ]),
                        Forms\Components\Repeater::make('notifications.whatsapp.templates.absent')
                            ->label('Pesan Tidak Hadir (Alpa)')
                            ->reorderableWithButtons()
                            ->collapsible()
                            ->cloneable()
                            ->deleteAction(
                                fn (Action $action) => $action->requiresConfirmation(),
                            )
                            ->addActionLabel('Tambah Template Tidak Hadir')
                            ->schema([
                                Forms\Components\Textarea::make('message')
                                    ->label('Isi Pesan')
                                    ->rows(3)
                                    ->default('Yth. Bapak/Ibu, ananda {nama_siswa} tidak tercatat hadir di sekolah pada tanggal {tanggal}. Mohon konfirmasinya.')
                                    ->helperText('Variabel: {nama_siswa}, {tanggal}, {kelas}'),
                            ]),
                        Forms\Components\Repeater::make('notifications.whatsapp.templates.present')
                            ->label('Pesan Hadir')
                            ->reorderableWithButtons()
                            ->collapsible()
                            ->cloneable()
                            ->deleteAction(
                                fn (Action $action) => $action->requiresConfirmation(),
                            )
                            ->addActionLabel('Tambah Template Hadir')
                            ->schema([
                                Forms\Components\Textarea::make('message')
                                    ->label('Isi Pesan')
                                    ->rows(3)
                                    ->default('Informasi: Ananda {nama_siswa} telah tercatat hadir di sekolah pada jam {jam_masuk}.')
                                    ->helperText('Variabel: {nama_siswa}, {jam_masuk}, {kelas}'),
                            ])
                    ]),
            ])
            ->statePath('data');
    }

    public function submit(): void
    {
        // 1. Get the nested state from the form.
        $data = $this->form->getState();

        // 2. Flatten the nested array back to a dot-notation array.
        $flattenedData = \Illuminate\Support\Arr::dot($data);

        // 3. Iterate over the flattened data and save each key-value pair.
        foreach ($flattenedData as $key => $value) {
            // Determine type based on value
            $type = 'string';
            if (is_bool($value)) {
                $type = 'boolean';
            } elseif (is_int($value)) {
                $type = 'integer';
            } elseif (is_float($value)) {
                $type = 'float';
            } elseif (is_array($value)) {
                // This handles the Repeater and CheckboxList data
                $type = 'json';
                $value = json_encode($value);
            }

            // Determine group name from key
            $groupName = strpos($key, '.') !== false ? explode('.', $key)[0] : 'general';

            Setting::updateOrCreate(
                ['key' => $key],
                [
                    'value' => $value,
                    'type' => $type,
                    'group_name' => $groupName,
                    'updated_by' => Auth::id(),
                ]
            );
        }

        Notification::make()
            ->title('Pengaturan berhasil disimpan!')
            ->body('Semua konfigurasi telah diperbarui.')
            ->success()
            ->send();
    }

}
