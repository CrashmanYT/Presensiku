<?php

namespace App\Filament\Actions;

use App\Exports\TemplateExport;
use Filament\Tables\Actions\Action;
use Illuminate\Http\Response;
use Maatwebsite\Excel\Facades\Excel;

class DownloadTemplateAction
{
    public static function make(string $type): Action
    {
        return Action::make('download_template')
            ->label('Download Template')
            ->icon('heroicon-o-document-arrow-down')
            ->color('gray')
            ->action(function () use ($type) {
                return self::downloadTemplate($type);
            });
    }

    protected static function downloadTemplate(string $type): Response
    {
        $templates = [
            'student' => [
                'filename' => 'Template_Import_Siswa.xlsx',
                'headers' => ['name', 'nis', 'class_name', 'gender', 'fingerprint_id', 'parent_whatsapp'],
                'sample_data' => [
                    ['Ahmad Rizki', '2023001', '12 IPA 1', 'L', '001', '+6281234567890'],
                    ['Siti Nurhaliza', '2023002', '12 IPA 1', 'P', '002', '+6281234567891'],
                    ['Budi Santoso', '2023003', '12 IPS 1', 'L', '003', '+6281234567892'],
                ],
            ],
            'teacher' => [
                'filename' => 'Template_Import_Guru.xlsx',
                'headers' => ['name', 'nip', 'fingerprint_id', 'whatsapp_number'],
                'sample_data' => [
                    ['Dr. Ahmad Malik', '196512151990031001', '101', '+6281234567800'],
                    ['Siti Aminah S.Pd', '197203102000032002', '102', '+6281234567801'],
                    ['Budi Rahman M.Pd', '198005151995121003', '103', '+6281234567802'],
                ],
            ],
            'class' => [
                'filename' => 'Template_Import_Kelas.xlsx',
                'headers' => ['name', 'level', 'major', 'homeroom_teacher_name'],
                'sample_data' => [
                    ['12 IPA 1', '12', 'IPA', 'Dr. Ahmad Malik'],
                    ['12 IPA 2', '12', 'IPA', 'Siti Aminah S.Pd'],
                    ['12 IPS 1', '12', 'IPS', 'Budi Rahman M.Pd'],
                ],
            ],
        ];

        if (! isset($templates[$type])) {
            abort(404, 'Template not found');
        }

        $template = $templates[$type];

        return Excel::download(
            new TemplateExport($template['headers'], $template['sample_data']),
            $template['filename']
        );
    }
}
