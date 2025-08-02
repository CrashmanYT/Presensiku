<?php

namespace Tests\Unit\Helpers;

use App\Helpers\ExportColumnHelper;
use PHPUnit\Framework\Attributes\Test;
use pxlrbt\FilamentExcel\Columns\Column;
use Tests\TestCase;

class ExportColumnHelperTest extends TestCase
{
    #[Test]
    public function it_returns_correct_student_columns()
    {
        $columns = ExportColumnHelper::getStudentColumns();

        $this->assertIsArray($columns);
        $this->assertContainsOnlyInstancesOf(Column::class, $columns);

        $headings = collect($columns)->map(fn(Column $column) => $this->getHeading($column))->all();
        $this->assertEquals([
            'Nama',
            'NIS',
            'Kelas',
            'Jenis Kelamin',
            'ID Sidik Jari',
            'Photo',
            'Nomor WA Orang Tua',
            'Tanggal Dibuat',
            'Tanggal Diubah',
        ], $headings);
    }

    #[Test]
    public function it_returns_correct_student_attendance_columns()
    {
        $columns = ExportColumnHelper::getStudentAttendanceColumns();

        $this->assertIsArray($columns);
        $this->assertContainsOnlyInstancesOf(Column::class, $columns);

        $headings = collect($columns)->map(fn(Column $column) => $this->getHeading($column))->all();
        $this->assertEquals([
            'Nama Siswa',
            'Kelas',
            'Tanggal',
            'Jam Masuk',
            'Jam Keluar',
            'Status',
            'Perangkat',
        ], $headings);
    }

    #[Test]
    public function it_returns_correct_teacher_columns()
    {
        $columns = ExportColumnHelper::getTeacherColumns();

        $this->assertIsArray($columns);
        $this->assertContainsOnlyInstancesOf(Column::class, $columns);

        $headings = collect($columns)->map(fn(Column $column) => $this->getHeading($column))->all();
        $this->assertEquals([
            'Nama',
            'No Induk',
            'Nomor WA',
            'ID Sidik Jari',
            'Photo',
            'Tanggal Dibuat',
            'Tanggal Diubah',
        ], $headings);
    }

    #[Test]
    public function it_returns_correct_teacher_attendance_columns()
    {
        $columns = ExportColumnHelper::getTeacherAttendanceColumns();

        $this->assertIsArray($columns);
        $this->assertContainsOnlyInstancesOf(Column::class, $columns);

        $headings = collect($columns)->map(fn(Column $column) => $this->getHeading($column))->all();
        $this->assertEquals([
            'Nama Guru',
            'Tanggal',
            'Jam Masuk',
            'Jam Keluar',
            'Status',
            'Perangkat',
        ], $headings);
    }

    #[Test]
    public function it_returns_correct_class_columns()
    {
        $columns = ExportColumnHelper::getClassColumns();

        $this->assertIsArray($columns);
        $this->assertContainsOnlyInstancesOf(Column::class, $columns);

        $headings = collect($columns)->map(fn(Column $column) => $this->getHeading($column))->all();
        $this->assertEquals([
            'Nama Kelas',
            'Level',
            'Jurusan',
            'Wali Kelas',
            'Tanggal Dibuat',
            'Tanggal Diubah',
        ], $headings);
    }

    private function getHeading(Column $column): string
    {
        return (new \ReflectionClass($column))->getProperty('heading')->getValue($column);
    }
}
