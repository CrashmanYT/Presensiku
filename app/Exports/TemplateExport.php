<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class TemplateExport implements FromArray, WithHeadings, WithStyles, ShouldAutoSize, WithEvents
{
    private array $headers;
    private array $sampleData;

    public function __construct(array $headers, array $sampleData = [])
    {
        $this->headers = $headers;
        $this->sampleData = $sampleData;
    }

    public function array(): array
    {
        return $this->sampleData;
    }

    public function headings(): array
    {
        return $this->headers;
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Style the first row as header
            1 => [
                'font' => [
                    'bold' => true,
                    'size' => 12,
                    'color' => ['rgb' => 'FFFFFF'],
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '4472C4'],
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
            ],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                
                // Get the highest row and column
                $highestRow = $sheet->getHighestRow();
                $highestColumn = $sheet->getHighestColumn();
                
                // Apply borders to all data
                if ($highestRow > 1) {
                    $sheet->getStyle('A2:' . $highestColumn . $highestRow)->applyFromArray([
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
                }
                
                // Set row height
                $sheet->getDefaultRowDimension()->setRowHeight(20);
                $sheet->getRowDimension(1)->setRowHeight(25);
                
                // Freeze first row
                $sheet->freezePane('A2');
                
                // Add instruction sheet
                $this->addInstructions($event);
            },
        ];
    }

    private function addInstructions(AfterSheet $event)
    {
        $sheet = $event->sheet->getDelegate();
        $workbook = $sheet->getParent();
        
        // Create new worksheet for instructions
        $instructionSheet = $workbook->createSheet();
        $instructionSheet->setTitle('Petunjuk Import');
        
        $instructions = [
            ['PETUNJUK PENGGUNAAN TEMPLATE IMPORT'],
            [''],
            ['1. Jangan mengubah nama kolom pada sheet "Template"'],
            ['2. Isi data sesuai dengan format yang telah ditentukan'],
            ['3. Kolom yang wajib diisi tidak boleh kosong'],
            ['4. Gunakan format yang benar untuk setiap kolom'],
            ['5. Simpan file dalam format Excel (.xlsx) atau CSV (.csv)'],
            [''],
            ['KETERANGAN KOLOM:'],
        ];
        
        // Add column descriptions based on headers
        $columnDescriptions = $this->getColumnDescriptions();
        foreach ($columnDescriptions as $description) {
            $instructions[] = $description;
        }
        
        $instructions[] = [''];
        $instructions[] = ['CONTOH FORMAT:'];
        $instructions[] = ['- Jenis Kelamin: L atau P (untuk Laki-laki atau Perempuan)'];
        $instructions[] = ['- Nomor WhatsApp: +6281234567890'];
        $instructions[] = ['- Level Kelas: 10, 11, atau 12'];
        $instructions[] = [''];
        $instructions[] = ['Jika ada pertanyaan, hubungi administrator sistem.'];
        
        // Write instructions to sheet
        $instructionSheet->fromArray($instructions, null, 'A1');
        
        // Style the instruction sheet
        $instructionSheet->getStyle('A1')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 14,
                'color' => ['rgb' => '000000'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'FFE699'],
            ],
        ]);
        
        $instructionSheet->getColumnDimension('A')->setWidth(80);
        $instructionSheet->getStyle('A:A')->getAlignment()->setWrapText(true);
        
        // Set the first sheet (template) as active
        $workbook->setActiveSheetIndex(0);
    }

    private function getColumnDescriptions(): array
    {
        $descriptions = [];
        
        foreach ($this->headers as $header) {
            switch ($header) {
                case 'name':
                    $descriptions[] = ['- name: Nama lengkap (wajib)'];
                    break;
                case 'nis':
                    $descriptions[] = ['- nis: Nomor Induk Siswa, harus unik (wajib)'];
                    break;
                case 'nip':
                    $descriptions[] = ['- nip: Nomor Induk Pegawai, harus unik (wajib)'];
                    break;
                case 'class_name':
                    $descriptions[] = ['- class_name: Nama kelas yang sudah ada di sistem (wajib)'];
                    break;
                case 'gender':
                    $descriptions[] = ['- gender: Jenis kelamin L/P atau LAKI-LAKI/PEREMPUAN (wajib)'];
                    break;
                case 'fingerprint_id':
                    $descriptions[] = ['- fingerprint_id: ID sidik jari (opsional)'];
                    break;
                case 'parent_whatsapp':
                    $descriptions[] = ['- parent_whatsapp: Nomor WhatsApp orang tua format +62xxx (opsional)'];
                    break;
                case 'whatsapp_number':
                    $descriptions[] = ['- whatsapp_number: Nomor WhatsApp format +62xxx (wajib)'];
                    break;
                case 'level':
                    $descriptions[] = ['- level: Tingkat kelas 1-12 (wajib)'];
                    break;
                case 'major':
                    $descriptions[] = ['- major: Jurusan kelas (wajib)'];
                    break;
                case 'homeroom_teacher_name':
                    $descriptions[] = ['- homeroom_teacher_name: Nama wali kelas yang sudah ada di sistem (opsional)'];
                    break;
            }
        }
        
        return $descriptions;
    }
}
