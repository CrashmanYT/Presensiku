<?php

namespace App\Filament\Resources;

use App\Enums\AttendanceStatusEnum;
use App\Contracts\SettingsRepositoryInterface;
use App\Services\MessageTemplateService;
use App\Filament\Resources\StudentAttendanceResource\Pages;
use App\Helpers\ExportColumnHelper;
use App\Models\StudentAttendance;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Notifications\Notification;

class StudentAttendanceResource extends Resource
{
    protected static ?string $model = StudentAttendance::class;

    protected static ?string $navigationGroup = 'Manajemen Absensi';

    protected static ?string $navigationLabel = 'Absensi Murid';

    protected static ?string $label = 'Absensi Murid';

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?int $navigationSort = 1;

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('student.name')
                    ->searchable()
                    ->label('Nama Siswa')
                    ->toggleable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('student.class.name')
                    ->searchable()
                    ->label('Kelas')
                    ->toggleable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('date')
                    ->date()
                    ->label('Tanggal')
                    ->toggleable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('time_in')
                    ->time('H:i')
                    ->toggleable()
                    ->label('Jam Masuk'),
                Tables\Columns\TextColumn::make('time_out')
                    ->time('H:i')
                    ->toggleable()
                    ->label('Jam Keluar'),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state instanceof \App\Enums\AttendanceStatusEnum ? $state->getLabel() : (string) $state)
                    ->color(fn ($state) => $state instanceof \App\Enums\AttendanceStatusEnum ? $state->getColor() : null)
                    ->icon(fn ($state) => $state instanceof \App\Enums\AttendanceStatusEnum ? $state->getIcon() : null)
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('device.name')
                    ->label('Perangkat')
                    ->toggleable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('date', 'desc')
            ->filters([
                SelectFilter::make('Nama')
                    ->relationship('student', 'name')
                    ->searchable()
                    ->multiple()
                    ->preload(),
                SelectFilter::make('Kelas')
                    ->relationship('student.class', 'name')
                    ->searchable()
                    ->multiple()
                    ->preload(),
                SelectFilter::make('Status')
                    ->options(AttendanceStatusEnum::labels())
                    ->multiple(),
                Filter::make('date_range')
                    ->label('Rentang Tanggal')
                    ->form([
                        DatePicker::make('from')->label('Dari'),
                        DatePicker::make('until')->label('Sampai'),

                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['from'], fn ($q, $date) => $q->whereDate('date', '>=', $date))
                            ->when($data['until'], fn ($q, $date) => $q->whereDate('date', '<=', $date));

                    }),

                Filter::make('waktu_masuk')
                    ->label('Jam Masuk')
                    ->form([
                        TimePicker::make('jam_masuk'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['jam_masuk'], fn ($q, $time) => $q->whereTime('time_in', $time));
                    }),
                Filter::make('waktu_keluar')
                    ->label('Jam Keluar')
                    ->form([
                        TimePicker::make('jam_keluar'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['jam_keluar'], fn ($q, $time) => $q->whereTime('time_out', $time));
                    }),

            ])
            ->filtersLayout(filtersLayout: FiltersLayout::Modal)
            ->filtersFormWidth('5xl')
            ->filtersFormColumns(2)
            ->headerActions([
                //                ExportAction::make('export')
                //                    ->label('Export Absensi Murid')
                //                    ->icon('heroicon-o-arrow-up-tray')
                //                    ->exports([
                //                        ExcelExport::make('absensimurid')->withColumns([
                //                            Column::make('student.name')->heading('Nama Siswa'),
                //                            Column::make('class.name')->heading('Kelas'),
                //                            Column::make('status')->heading('Status'),
                //                            Column::make('date')->heading('Tanggal'),
                //                            Column::make('time_in')->heading('Jam Masuk'),
                //                            Column::make('time_out')->heading('Jam Keluar'),
                //                        ])->modifyQueryUsing(function ($query, array $data) {
                //                            return $query
                //                                ->join('students', 'student_attendances.student_id', '=', 'students.id')
                //                                ->join('classes', 'students.class_id', '=', 'classes.id')
                //                                ->leftJoin('devices', 'student_attendances.device_id', '=', 'devices.id')
                //                                ->select(
                //                                    'student_attendances.*',
                //                                    'students.name as student_name',
                //                                    'classes.name as class_name',
                //                                    'devices.name as device_name',
                //                                );
                //                        })
                //                            ->queue()
                //                            ->chunkSize(200)
                //                    ])
            ])
            ->headerActions([
                
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->label('Edit'),
                Tables\Actions\Action::make('ubah_status')
                    ->label('')
                    ->tooltip('Ubah Status')
                    ->icon('heroicon-o-pencil-square')
                    ->iconButton()
                    ->color('warning')
                    ->form(function (\App\Models\StudentAttendance $record): array {
                        return [
                            Forms\Components\Select::make('status')
                                ->label('Status')
                                ->options(AttendanceStatusEnum::labels())
                                ->required()
                                ->default($record->status instanceof AttendanceStatusEnum ? $record->status->value : ($record->status ?? null)),
                        ];
                    })
                    ->action(function (\App\Models\StudentAttendance $record, array $data): void {
                        $record->status = $data['status'];
                        $record->save();

                        Notification::make()
                            ->title('Status kehadiran diperbarui')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\Action::make('kirim_wa')
                    ->label('')
                    ->tooltip('Kirim WA')
                    ->icon('heroicon-o-paper-airplane')
                    ->iconButton()
                    ->color('success')
                    ->visible(fn (\App\Models\StudentAttendance $record) => filled(optional($record->student)->parent_whatsapp))
                    ->form(function (\App\Models\StudentAttendance $record): array {
                        // Base variables for template interpolation
                        $studentName = $record->student->name ?? '-';
                        $date = \Illuminate\Support\Carbon::parse($record->date)->format('d M Y');
                        $timeIn = $record->time_in ? substr((string) $record->time_in, 0, 5) : '-';
                        $statusLabel = $record->status instanceof AttendanceStatusEnum ? $record->status->getLabel() : (string) ($record->status ?? '-');

                        $variables = [
                            'student_name' => $studentName,
                            'date' => $date,
                            'time_in' => $timeIn,
                            'status_label' => $statusLabel,
                        ];

                        // Build plain fallback
                        $plainFallback = "Assalamualaikum, Orang Tua/Wali dari {$studentName}.\n" .
                            "Informasi kehadiran tanggal {$date}:\n" .
                            "Status: {$statusLabel}\n" .
                            "Jam Masuk: {$timeIn}.";

                        // Collect template types from settings (nested or flat)
                        $templateOptions = [];
                        try {
                            /** @var SettingsRepositoryInterface $settings */
                            $settings = app(SettingsRepositoryInterface::class);
                            $root = $settings->get('notifications.whatsapp.templates', []);
                            if (!is_array($root) || empty($root)) {
                                $nested = $settings->allAsNested();
                                $root = $nested['notifications']['whatsapp']['templates'] ?? [];
                            }
                            if (is_array($root)) {
                                $keys = array_keys($root);
                                $templateOptions = array_combine($keys, $keys);
                            }
                        } catch (\Throwable $e) {
                            $templateOptions = [];
                        }

                        // Guess default template type from status
                        $statusKey = $record->status instanceof AttendanceStatusEnum ? $record->status->value : (string) ($record->status ?? '');
                        $guessed = match ($statusKey) {
                            'hadir' => 'student_present',
                            'terlambat' => 'student_late',
                            'tidak_hadir' => 'student_absent',
                            'izin' => 'student_leave',
                            'sakit' => 'student_sick',
                            default => null,
                        };
                        if (empty($templateOptions) || ($guessed !== null && !array_key_exists($guessed, $templateOptions))) {
                            $guessed = array_key_first($templateOptions) ?: null;
                        }

                        // Try initial render from template
                        $defaultMessage = $plainFallback;
                        if ($guessed) {
                            try {
                                /** @var MessageTemplateService $mts */
                                $mts = app(MessageTemplateService::class);
                                $rendered = (string) $mts->renderByType($guessed, $variables);
                                if ($rendered !== '') {
                                    $defaultMessage = $rendered;
                                }
                            } catch (\Throwable $e) {
                                // keep fallback
                            }
                        }

                        return [
                            Forms\Components\Toggle::make('use_template')
                                ->label('Gunakan Template')
                                ->default(!empty($templateOptions))
                                ->reactive()
                                ->afterStateUpdated(function ($state, \Filament\Forms\Set $set, \Filament\Forms\Get $get) use ($variables, $plainFallback) {
                                    if (!$state) {
                                        $set('message', $plainFallback);
                                        return;
                                    }
                                    $type = (string) ($get('template_type') ?? '');
                                    if ($type === '') {
                                        $set('message', $plainFallback);
                                        return;
                                    }
                                    try {
                                        /** @var MessageTemplateService $mts */
                                        $mts = app(MessageTemplateService::class);
                                        $rendered = (string) $mts->renderByType($type, $variables);
                                        $set('message', $rendered !== '' ? $rendered : $plainFallback);
                                    } catch (\Throwable $e) {
                                        $set('message', $plainFallback);
                                    }
                                }),

                            Forms\Components\Select::make('template_type')
                                ->label('Tipe Template')
                                ->options($templateOptions)
                                ->searchable()
                                ->preload()
                                ->default($guessed)
                                ->visible(fn (\Filament\Forms\Get $get) => (bool) $get('use_template'))
                                ->reactive()
                                ->afterStateUpdated(function ($state, \Filament\Forms\Set $set, \Filament\Forms\Get $get) use ($variables, $plainFallback) {
                                    if (!(bool) $get('use_template')) {
                                        return;
                                    }
                                    try {
                                        /** @var MessageTemplateService $mts */
                                        $mts = app(MessageTemplateService::class);
                                        $rendered = (string) $mts->renderByType((string) $state, $variables);
                                        $set('message', $rendered !== '' ? $rendered : $plainFallback);
                                    } catch (\Throwable $e) {
                                        $set('message', $plainFallback);
                                    }
                                }),

                            Forms\Components\View::make('filament.student_attendance._wa_template_help'),

                            Forms\Components\Textarea::make('message')
                                ->label('Pesan WhatsApp')
                                ->rows(7)
                                ->required()
                                ->default($defaultMessage)
                                ->helperText('Placeholder: {student_name}, {date}, {time_in}, {status_label}, dan {v:key} untuk variasi frasa.'),
                        ];
                    })
                    ->action(function (\App\Models\StudentAttendance $record, array $data): void {
                        $receiver = optional($record->student)->parent_whatsapp;

                        // Build variables (with aliases) to maximize template compatibility
                        $studentName = optional($record->student)->name ?? '-';
                        $className = optional(optional($record->student)->class)->name ?? '-';
                        $deviceName = optional($record->device)->name ?? '-';
                        $date = \Illuminate\Support\Carbon::parse($record->date)->format('d M Y');
                        $timeIn = $record->time_in ? substr((string) $record->time_in, 0, 5) : '-';
                        $timeOut = $record->time_out ? substr((string) $record->time_out, 0, 5) : '-';
                        $statusLabel = $record->status instanceof AttendanceStatusEnum ? $record->status->getLabel() : (string) ($record->status ?? '-');
                        $expectedTime = '-'; // TODO: derive from schedule/rules if available

                        $variables = [
                            // English-ish keys
                            'student_name' => $studentName,
                            'class_name' => $className,
                            'device_name' => $deviceName,
                            'date' => $date,
                            'time_in' => $timeIn,
                            'time_out' => $timeOut,
                            'status_label' => $statusLabel,
                            'expected_time' => $expectedTime,
                            // Indonesian aliases
                            'nama_siswa' => $studentName,
                            'kelas' => $className,
                            'perangkat' => $deviceName,
                            'tanggal' => $date,
                            'jam_masuk' => $timeIn,
                            'jam_keluar' => $timeOut,
                            'status' => $statusLabel,
                            'jam_seharusnya' => $expectedTime,
                        ];

                        $message = (string) ($data['message'] ?? '');
                        $useTemplate = (bool) ($data['use_template'] ?? false);
                        $templateType = (string) ($data['template_type'] ?? '');

                        try {
                            /** @var \App\Services\MessageTemplateService $mts */
                            $mts = app(\App\Services\MessageTemplateService::class);
                            if ($useTemplate && $templateType !== '') {
                                // Authoritative render by type at send time
                                $rendered = (string) $mts->renderByType($templateType, $variables);
                                if ($rendered !== '') {
                                    $message = $rendered;
                                }
                            }
                            // Always post-process to ensure placeholders resolved
                            $message = $mts->expandVariants($message);
                            $message = $mts->interpolate($message, $variables);
                        } catch (\Throwable $e) {
                            // Keep message as-is on failure
                        }

                        // As a safety net, replace any leftover {key} with '-'
                        $message = preg_replace('/\{[A-Za-z0-9_\.-]+\}/', '-', (string) $message) ?? (string) $message;

                        $result = app(\App\Services\WhatsappService::class)->sendMessage((string) $receiver, $message);

                        if (($result['success'] ?? false) === true) {
                            Notification::make()
                                ->title('Pesan WhatsApp terkirim')
                                ->success()
                                ->send();
                        } else {
                            $error = $result['error'] ?? 'Gagal mengirim pesan.';
                            Notification::make()
                                ->title('Gagal mengirim WhatsApp')
                                ->body($error)
                                ->danger()
                                ->send();
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('bulk_ubah_status')
                        ->label('Ubah Status')
                        ->icon('heroicon-o-pencil-square')
                        ->color('warning')
                        ->form([
                            Forms\Components\Select::make('status')
                                ->label('Status')
                                ->options(AttendanceStatusEnum::labels())
                                ->required(),
                        ])
                        ->action(function (\Illuminate\Support\Collection $records, array $data): void {
                            $updated = 0;
                            foreach ($records as $record) {
                                /** @var \App\Models\StudentAttendance $record */
                                $record->status = (string) $data['status'];
                                $record->save();
                                $updated++;
                            }
                            Notification::make()
                                ->title('Status diperbarui')
                                ->body("Berhasil memperbarui status pada {$updated} data.")
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\BulkAction::make('bulk_kirim_wa')
                        ->label('Kirim WA')
                        ->icon('heroicon-o-paper-airplane')
                        ->color('success')
                        ->form(function (): array {
                            // Build template options from settings
                            $templateOptions = [];
                            try {
                                /** @var \App\Contracts\SettingsRepositoryInterface $settings */
                                $settings = app(\App\Contracts\SettingsRepositoryInterface::class);
                                $root = $settings->get('notifications.whatsapp.templates', []);
                                if (!is_array($root) || empty($root)) {
                                    $nested = $settings->allAsNested();
                                    $root = $nested['notifications']['whatsapp']['templates'] ?? [];
                                }
                                if (is_array($root)) {
                                    $keys = array_keys($root);
                                    $templateOptions = array_combine($keys, $keys);
                                }
                            } catch (\Throwable $e) {
                                $templateOptions = [];
                            }

                            return [
                                Forms\Components\Toggle::make('use_template')
                                    ->label('Gunakan Template')
                                    ->default(!empty($templateOptions))
                                    ->reactive(),
                                Forms\Components\Select::make('template_type')
                                    ->label('Tipe Template')
                                    ->options($templateOptions)
                                    ->searchable()
                                    ->preload()
                                    ->visible(fn (\Filament\Forms\Get $get) => (bool) $get('use_template')),
                                Forms\Components\Textarea::make('message')
                                    ->label('Pesan WhatsApp (opsional jika pakai template)')
                                    ->rows(7)
                                    ->helperText('Placeholder: {student_name}/{nama_siswa}, {date}/{tanggal}, {time_in}/{jam_masuk}, {status_label}/{status}, {v:key}.'),
                            ];
                        })
                        ->action(function (\Illuminate\Support\Collection $records, array $data): void {
                            $countSent = 0; $countSkipped = 0; $countFailed = 0;
                            $useTemplate = (bool) ($data['use_template'] ?? false);
                            $templateType = (string) ($data['template_type'] ?? '');
                            $baseMessage = (string) ($data['message'] ?? '');

                            /** @var \App\Services\MessageTemplateService $mts */
                            $mts = app(\App\Services\MessageTemplateService::class);
                            /** @var \App\Services\WhatsappService $wa */
                            $wa = app(\App\Services\WhatsappService::class);

                            foreach ($records as $record) {
                                /** @var \App\Models\StudentAttendance $record */
                                $receiver = optional($record->student)->parent_whatsapp;
                                if (!filled($receiver)) { $countSkipped++; continue; }

                                // Build variables with aliases per record
                                $studentName = optional($record->student)->name ?? '-';
                                $className = optional(optional($record->student)->class)->name ?? '-';
                                $deviceName = optional($record->device)->name ?? '-';
                                $date = \Illuminate\Support\Carbon::parse($record->date)->format('d M Y');
                                $timeIn = $record->time_in ? substr((string) $record->time_in, 0, 5) : '-';
                                $timeOut = $record->time_out ? substr((string) $record->time_out, 0, 5) : '-';
                                $statusLabel = $record->status instanceof AttendanceStatusEnum ? $record->status->getLabel() : (string) ($record->status ?? '-');
                                $expectedTime = '-';

                                $variables = [
                                    'student_name' => $studentName,
                                    'class_name' => $className,
                                    'device_name' => $deviceName,
                                    'date' => $date,
                                    'time_in' => $timeIn,
                                    'time_out' => $timeOut,
                                    'status_label' => $statusLabel,
                                    'expected_time' => $expectedTime,
                                    'nama_siswa' => $studentName,
                                    'kelas' => $className,
                                    'perangkat' => $deviceName,
                                    'tanggal' => $date,
                                    'jam_masuk' => $timeIn,
                                    'jam_keluar' => $timeOut,
                                    'status' => $statusLabel,
                                    'jam_seharusnya' => $expectedTime,
                                ];

                                $message = $baseMessage;
                                try {
                                    if ($useTemplate && $templateType !== '') {
                                        $rendered = (string) $mts->renderByType($templateType, $variables);
                                        if ($rendered !== '') { $message = $rendered; }
                                    }
                                    $message = $mts->expandVariants($message);
                                    $message = $mts->interpolate($message, $variables);
                                    $message = preg_replace('/\{[A-Za-z0-9_\.-]+\}/', '-', (string) $message) ?? (string) $message;
                                } catch (\Throwable $e) {
                                    // keep as-is
                                }

                                $result = $wa->sendMessage((string) $receiver, (string) $message);
                                if (($result['success'] ?? false) === true) { $countSent++; }
                                else { $countFailed++; }
                            }

                            $body = "Terkirim: {$countSent}\nDilewati (tanpa no. WA): {$countSkipped}\nGagal: {$countFailed}";
                            $notif = Notification::make()
                                ->title('Hasil Kirim WhatsApp (Bulk)')
                                ->body($body);
                            if ($countFailed > 0) {
                                $notif->danger();
                            } else {
                                $notif->success();
                            }
                            $notif->send();
                        }),

                    Tables\Actions\DeleteBulkAction::make(),
                    
                ]),
            ]);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('student_id')
                    ->required()
                    ->relationship('student', 'name')
                    ->label('Nama Siswa')
                    ->searchable(),
                Forms\Components\DatePicker::make('date')
                    ->label('Tanggal')
                    ->required(),
                Forms\Components\TimePicker::make('time_in')
                    ->label('Jam Masuk'),
                Forms\Components\TimePicker::make('time_out')
                    ->label('Jam Keluar'),
                Forms\Components\Select::make('status')
                    ->options(AttendanceStatusEnum::class)
                    ->label('Status Kehadiran')
                    ->required(),
                Forms\Components\FileUpload::make('photo_in')
                    ->image()
                    ->label('Log Foto'),
                Forms\Components\Select::make('device_id')
                    ->label('Nama Device')
                    ->relationship('device', 'name'),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStudentAttendances::route('/'),
            'create' => Pages\CreateStudentAttendance::route('/create'),
            'view' => Pages\ViewStudentAttendance::route('/{record}'),
            'edit' => Pages\EditStudentAttendance::route('/{record}/edit'),
        ];
    }
}
