<?php

namespace App\Exports;

use App\Models\TeacherAttendance;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class TeacherAttendanceExport implements FromQuery, ShouldAutoSize, WithChunkReading, WithEvents, WithHeadings, WithMapping
{
    protected $filters;

    public function __construct($filters = [])
    {
        $this->filters = $filters;
    }

    public function query()
    {
        $query = TeacherAttendance::query()
            ->with(['teacher', 'device'])
            ->select('teacher_attendances.*')
            ->orderBy('date', 'desc')
            ->orderBy('id', 'desc');

        // Apply filters if provided
        if (! empty($this->filters['from_date'])) {
            $query->whereDate('date', '>=', $this->filters['from_date']);
        }

        if (! empty($this->filters['to_date'])) {
            $query->whereDate('date', '<=', $this->filters['to_date']);
        }

        if (! empty($this->filters['teacher_ids'])) {
            $query->whereIn('teacher_id', $this->filters['teacher_ids']);
        }

        if (! empty($this->filters['status'])) {
            $query->whereIn('status', $this->filters['status']);
        }

        return $query;
    }

    public function headings(): array
    {
        return [
            'No',
            'Nama Guru',
            'NIP',
            'Tanggal',
            'Jam Masuk',
            'Jam Keluar',
            'Status',
            'Perangkat',
            'Dibuat Pada',
        ];
    }

    public function map($attendance): array
    {
        static $counter = 0;
        $counter++;

        return [
            $counter,
            $attendance->teacher->name ?? '-',
            $attendance->teacher->nip ?? '-',
            $attendance->date ? Carbon::parse($attendance->date)->format('d/m/Y') : '-',
            $attendance->time_in ? Carbon::parse($attendance->time_in)->format('H:i') : '-',
            $attendance->time_out ? Carbon::parse($attendance->time_out)->format('H:i') : '-',
            $attendance->status ? $attendance->status->getLabel() : '-',
            $attendance->device->name ?? '-',
            $attendance->created_at ? $attendance->created_at->format('d/m/Y H:i') : '-',
        ];
    }

    public function chunkSize(): int
    {
        return 500; // Process 500 records at a time to manage memory
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // Get the highest row and column
                $highestRow = $sheet->getHighestRow();
                $highestColumn = $sheet->getHighestColumn();

                // Style the header row
                $sheet->getStyle('A1:'.$highestColumn.'1')->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'size' => 12,
                        'color' => ['rgb' => 'FFFFFF'],
                    ],
                    'fill' => [
                        'fillType' => 'solid',
                        'startColor' => ['rgb' => '2E8B57'],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => '000000'],
                        ],
                    ],
                ]);

                // Style data rows
                if ($highestRow > 1) {
                    $sheet->getStyle('A2:'.$highestColumn.$highestRow)->applyFromArray([
                        'borders' => [
                            'allBorders' => [
                                'borderStyle' => Border::BORDER_THIN,
                                'color' => ['rgb' => 'CCCCCC'],
                            ],
                        ],
                        'alignment' => [
                            'vertical' => Alignment::VERTICAL_CENTER,
                        ],
                    ]);

                    // Center align specific columns
                    $sheet->getStyle('A2:A'.$highestRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle('D2:D'.$highestRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle('E2:F'.$highestRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle('G2:G'.$highestRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                }

                // Set row height
                $sheet->getDefaultRowDimension()->setRowHeight(20);
                $sheet->getRowDimension(1)->setRowHeight(25);

                // Freeze first row
                $sheet->freezePane('A2');
            },
        ];
    }
}
