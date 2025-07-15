<?php

namespace App\Filament\Pages;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use App\Models\Setting;
use Filament\Notifications\Notification;

class Settings extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-cog';
    protected static ?string $navigationGroup = 'Pengaturan Sistem';

    protected static string $view = 'filament.pages.settings';

    protected static ?string $title = 'Pengaturan Umum';
    protected static ?string $navigationLabel = 'Pengaturan Umum';
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
                Forms\Components\Section::make('Pengaturan Umum')
                    ->schema([
                        Forms\Components\TextInput::make('wa_api_key')
                            ->label('WhatsApp API Key')
                            ->helperText('Kunci API untuk integrasi WhatsApp.'),
                        Forms\Components\TimePicker::make('default_time_in_start')
                            ->label('Default Jam Masuk Mulai'),
                        Forms\Components\TimePicker::make('default_time_in_end')
                            ->label('Default Jam Masuk Selesai'),
                        Forms\Components\TimePicker::make('default_time_out_start')
                            ->label('Default Jam Pulang Mulai'),
                        Forms\Components\TimePicker::make('default_time_out_end')
                            ->label('Default Jam Pulang Selesai'),
                        Forms\Components\TextInput::make('kesiswaan_whatsapp_number')
                            ->label('Nomor WA Kesiswaan/BK')
                            ->tel()
                            ->helperText('Nomor WhatsApp untuk notifikasi internal.'),
                    ]),
                Forms\Components\Section::make('Template Pesan WhatsApp')
                    ->schema([
                        Forms\Components\Textarea::make('wa_message_late')
                            ->label('Pesan Terlambat')
                            ->helperText('Gunakan {nama_siswa}, {jam_masuk}, {jam_seharusnya}.'),
                        Forms\Components\Textarea::make('wa_message_absent')
                            ->label('Pesan Tidak Hadir')
                            ->helperText('Gunakan {nama_siswa}, {tanggal}.'),
                        Forms\Components\Textarea::make('wa_message_present')
                            ->label('Pesan Hadir')
                            ->helperText('Gunakan {nama_siswa}, {jam_masuk}.'),
                    ]),
            ])
            ->statePath('data');
    }

    public function submit(): void
    {
        $data = $this->form->getState();

        foreach ($data as $key => $value) {
            Setting::updateOrCreate(
                ['key' => $key],
                ['value' => $value]
            );
        }

        Notification::make()
            ->title('Pengaturan berhasil disimpan!')
            ->success()
            ->send();
    }
}
