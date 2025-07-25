<?php

namespace App\Filament\Pages;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use App\Models\Setting;
use Filament\Notifications\Notification;
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
        $this->form->fill(Setting::pluck('value', 'key')->toArray());
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                // Dashboard Settings
                Forms\Components\Section::make('Pengaturan Dashboard')
                    ->description('Konfigurasi untuk realtime attendance dashboard')
                    ->icon('heroicon-o-computer-desktop')
                    ->schema([
                        Forms\Components\Toggle::make('dashboard.show_test_button')
                            ->label('Show Test Button')
                            ->default(true)
                            ->helperText('Tampilkan tombol test di environment local'),
                    ]),

                // Attendance Settings
                Forms\Components\Section::make('Pengaturan Absensi')
                    ->description('Konfigurasi default waktu masuk dan pulang')
                    ->icon('heroicon-o-finger-print')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TimePicker::make('default_time_in_start')
                                    ->label('Default Jam Masuk Mulai')
                                    ->default('07:00'),
                                Forms\Components\TimePicker::make('default_time_in_end')
                                    ->label('Default Jam Masuk Selesai')
                                    ->default('08:00'),
                                Forms\Components\TimePicker::make('default_time_out_start')
                                    ->label('Default Jam Pulang Mulai')
                                    ->default('15:00'),
                                Forms\Components\TimePicker::make('default_time_out_end')
                                    ->label('Default Jam Pulang Selesai')
                                    ->default('16:00'),
                            ]),
                    ]),

                // Notification Settings
                Forms\Components\Section::make('Pengaturan Notifikasi')
                    ->description('Konfigurasi sistem notifikasi WhatsApp dan lainnya')
                    ->icon('heroicon-o-bell')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Toggle::make('notification.enabled')
                                    ->label('Aktifkan Notifikasi')
                                    ->default(true)
                                    ->helperText('Enable/disable semua notifikasi'),
                                Forms\Components\TextInput::make('notification.late_threshold')
                                    ->label('Batas Waktu Terlambat (menit)')
                                    ->numeric()
                                    ->default(15)
                                    ->suffix('menit')
                                    ->helperText('Berapa menit setelah jam masuk dianggap terlambat'),
                            ]),
                        Forms\Components\TextInput::make('wa_api_key')
                            ->label('WhatsApp API Key')
                            ->password()
                            ->revealable()
                            ->default('')
                            ->helperText('Kunci API untuk integrasi WhatsApp'),
                        Forms\Components\TextInput::make('kesiswaan_whatsapp_number')
                            ->label('Nomor WA Kesiswaan/BK')
                            ->tel()
                            ->default('')
                            ->placeholder('62812345678')
                            ->helperText('Nomor WhatsApp untuk notifikasi internal'),
                        Forms\Components\TimePicker::make('notification.absent_notification_time')
                            ->label('Jam Kirim Notifikasi Tidak Hadir')
                            ->default('09:00')
                            ->helperText('Jam otomatis kirim notifikasi untuk yang tidak hadir'),
                        Forms\Components\CheckboxList::make('notification.channels')
                            ->label('Channel Notifikasi')
                            ->options([
                                'whatsapp' => 'WhatsApp',
                                'email' => 'Email',
                                'sms' => 'SMS',
                            ])
                            ->default(['whatsapp'])
                            ->helperText('Pilih channel notifikasi yang aktif'),
                    ]),

                // System Settings
                Forms\Components\Section::make('Pengaturan Sistem')
                    ->description('Konfigurasi umum sistem')
                    ->icon('heroicon-o-cog-6-tooth')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('system.timezone')
                                    ->label('Timezone')
                                    ->options([
                                        'Asia/Jakarta' => 'WIB (Asia/Jakarta)',
                                        'Asia/Makassar' => 'WITA (Asia/Makassar)',
                                        'Asia/Jayapura' => 'WIT (Asia/Jayapura)',
                                    ])
                                    ->default('Asia/Jakarta')
                                    ->helperText('Zona waktu aplikasi'),
                                Forms\Components\Select::make('system.date_format')
                                    ->label('Format Tanggal')
                                    ->options([
                                        'd/m/Y' => 'DD/MM/YYYY (25/07/2025)',
                                        'Y-m-d' => 'YYYY-MM-DD (2025-07-25)',
                                        'd-m-Y' => 'DD-MM-YYYY (25-07-2025)',
                                        'M d, Y' => 'MMM DD, YYYY (Jul 25, 2025)',
                                    ])
                                    ->default('d/m/Y')
                                    ->helperText('Format tampilan tanggal'),
                                Forms\Components\Select::make('system.language')
                                    ->label('Bahasa Default')
                                    ->options([
                                        'id' => 'Bahasa Indonesia',
                                        'en' => 'English',
                                    ])
                                    ->default('id')
                                    ->helperText('Bahasa default aplikasi'),
                                Forms\Components\Toggle::make('system.maintenance_mode')
                                    ->label('Mode Maintenance')
                                    ->default(false)
                                    ->helperText('Aktifkan mode maintenance'),
                            ]),
                    ]),

                // WhatsApp Templates
                Forms\Components\Section::make('Template Pesan WhatsApp')
                    ->description('Kustomisasi template pesan notifikasi WhatsApp')
                    ->icon('heroicon-o-chat-bubble-left-ellipsis')
                    ->schema([
                        Forms\Components\Textarea::make('wa_message_late')
                            ->label('Template Pesan Terlambat')
                            ->rows(3)
                            ->default('Halo {nama_siswa}, Anda terlambat masuk sekolah. Jam masuk: {jam_masuk}, seharusnya: {jam_seharusnya}')
                            ->helperText('Variabel: {nama_siswa}, {jam_masuk}, {jam_seharusnya}, {kelas}'),
                        Forms\Components\Textarea::make('wa_message_absent')
                            ->label('Template Pesan Tidak Hadir')
                            ->rows(3)
                            ->default('Halo, {nama_siswa} tidak hadir ke sekolah pada tanggal {tanggal}. Mohon konfirmasi.')
                            ->helperText('Variabel: {nama_siswa}, {tanggal}, {kelas}'),
                        Forms\Components\Textarea::make('wa_message_present')
                            ->label('Template Pesan Hadir')
                            ->rows(3)
                            ->default('Halo, {nama_siswa} telah hadir ke sekolah pada jam {jam_masuk}.')
                            ->helperText('Variabel: {nama_siswa}, {jam_masuk}, {kelas}'),
                    ]),
            ])
            ->statePath('data');
    }

    public function submit(): void
    {
        $data = $this->form->getState();

        foreach ($data as $key => $value) {
            // Determine type based on value
            $type = 'string';
            if (is_bool($value)) {
                $type = 'boolean';
            } elseif (is_int($value)) {
                $type = 'integer';
            } elseif (is_float($value)) {
                $type = 'float';
            } elseif (is_array($value)) {
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
