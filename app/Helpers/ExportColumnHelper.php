<?php

namespace App\Helpers;

use pxlrbt\FilamentExcel\Columns\Column;

class ExportColumnHelper
{
    /**
     * Get standard student export columns
     */
    public static function getStudentColumns(): array
    {
        return [
            Column::make('name')->heading('Nama'),
            Column::make('nis')->heading('NIS'),
            Column::make('class.name')->heading('Kelas'),
            Column::make('gender')->heading('Jenis Kelamin'),
            Column::make('fingerprint_id')->heading('ID Sidik Jari'),
            Column::make('photo')->heading('Photo'),
            Column::make('parent_whatsapp')->heading('Nomor WA Orang Tua'),
            Column::make('created_at')->heading('Tanggal Dibuat'),
            Column::make('updated_at')->heading('Tanggal Diubah'),
        ];
    }

    /**
     * Get standard student attendance export columns
     */
    public static function getStudentAttendanceColumns(): array
    {
        return [
            Column::make('student.name')->heading('Nama Siswa'),
            Column::make('student.class.name')->heading('Kelas'),
            Column::make('date')->heading('Tanggal'),
            Column::make('time_in')->heading('Jam Masuk'),
            Column::make('time_out')->heading('Jam Keluar'),
            Column::make('status')->heading('Status'),
            Column::make('device.name')->heading('Perangkat'),
        ];
    }

    /**
     * Get standard teacher export columns
     */
    public static function getTeacherColumns(): array
    {
        return [
            Column::make('name')->heading('Nama'),
            Column::make('nip')->heading('No Induk'),
            Column::make('whatsapp_number')->heading('Nomor WA'),
            Column::make('fingerprint_id')->heading('ID Sidik Jari'),
            Column::make('photo')->heading('Photo'),
            Column::make('created_at')->heading('Tanggal Dibuat'),
            Column::make('updated_at')->heading('Tanggal Diubah'),
        ];
    }

    /**
     * Get standard teacher attendance export columns
     */
    public static function getTeacherAttendanceColumns(): array
    {
        return [
            Column::make('teacher.name')->heading('Nama Guru'),
            Column::make('date')->heading('Tanggal'),
            Column::make('time_in')->heading('Jam Masuk'),
            Column::make('time_out')->heading('Jam Keluar'),
            Column::make('status')->heading('Status'),
            Column::make('device.name')->heading('Perangkat'),
        ];
    }

    /**
     * Get standard class export columns
     */
    public static function getClassColumns(): array
    {
        return [
            Column::make('name')->heading('Nama Kelas'),
            Column::make('level')->heading('Level'),
            Column::make('major')->heading('Jurusan'),
            Column::make('homeroomTeacher.name')->heading('Wali Kelas'),
            Column::make('created_at')->heading('Tanggal Dibuat'),
            Column::make('updated_at')->heading('Tanggal Diubah'),
        ];
    }
}
