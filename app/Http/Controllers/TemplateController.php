<?php

namespace App\Http\Controllers;

use App\Exports\TemplateExport;
use App\Models\Classes;
use App\Models\Teacher;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Controller to provide downloadable Excel templates for bulk import.
 *
 * Provides sample data and headers to guide users when preparing
 * bulk import files for students, teachers, and classes.
 */
class TemplateController extends Controller
{
    /**
     * Download the student import template (XLSX) with example rows.
     *
     * Headers: name, nis, class_name, gender, fingerprint_id, parent_whatsapp
     *
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function downloadStudentTemplate()
    {
        $headers = ['name', 'nis', 'class_name', 'gender', 'fingerprint_id', 'parent_whatsapp'];

        // Get actual class names from database for sample data
        $classes = Classes::limit(3)->pluck('name')->toArray();
        $sampleClasses = ! empty($classes) ? $classes : ['12 IPA 1', '12 IPA 2', '12 IPS 1'];

        $sampleData = [
            ['Ahmad Rizki', '2023001', $sampleClasses[0] ?? '12 IPA 1', 'L', '001', '+6281234567890'],
            ['Siti Nurhaliza', '2023002', $sampleClasses[0] ?? '12 IPA 1', 'P', '002', '+6281234567891'],
            ['Budi Santoso', '2023003', $sampleClasses[1] ?? '12 IPS 1', 'L', '003', '+6281234567892'],
        ];

        return Excel::download(
            new TemplateExport($headers, $sampleData),
            'Template_Import_Siswa.xlsx'
        );
    }

    /**
     * Download the teacher import template (XLSX) with example rows.
     *
     * Headers: name, nip, fingerprint_id, whatsapp_number
     *
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function downloadTeacherTemplate()
    {
        $headers = ['name', 'nip', 'fingerprint_id', 'whatsapp_number'];

        $sampleData = [
            ['Dr. Ahmad Malik', '196512151990031001', '101', '+6281234567800'],
            ['Siti Aminah S.Pd', '197203102000032002', '102', '+6281234567801'],
            ['Budi Rahman M.Pd', '198005151995121003', '103', '+6281234567802'],
        ];

        return Excel::download(
            new TemplateExport($headers, $sampleData),
            'Template_Import_Guru.xlsx'
        );
    }

    /**
     * Download the class import template (XLSX) with example rows.
     *
     * Headers: name, level, major, homeroom_teacher_name
     *
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function downloadClassTemplate()
    {
        $headers = ['name', 'level', 'major', 'homeroom_teacher_name'];

        // Get actual teacher names from database for sample data
        $teachers = Teacher::limit(3)->pluck('name')->toArray();
        $sampleTeachers = ! empty($teachers) ? $teachers : ['Dr. Ahmad Malik', 'Siti Aminah S.Pd', 'Budi Rahman M.Pd'];

        $sampleData = [
            ['12 IPA 1', '12', 'IPA', $sampleTeachers[0] ?? 'Dr. Ahmad Malik'],
            ['12 IPA 2', '12', 'IPA', $sampleTeachers[1] ?? 'Siti Aminah S.Pd'],
            ['12 IPS 1', '12', 'IPS', $sampleTeachers[2] ?? 'Budi Rahman M.Pd'],
        ];

        return Excel::download(
            new TemplateExport($headers, $sampleData),
            'Template_Import_Kelas.xlsx'
        );
    }
}
