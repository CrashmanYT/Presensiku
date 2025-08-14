<?php

namespace App\Console\Commands;

use App\Models\Student;
use App\Models\Teacher;
use App\Support\PhoneNumber;
use Illuminate\Console\Command;

class NormalizePhoneNumbers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * Options:
     *  --dry-run    : Do not write changes, only report would-be changes
     *  --only=      : Limit to 'teachers' or 'students' (default: both)
     *  --chunk=     : Chunk size (default: 500)
     */
    protected $signature = 'normalize:phones {--dry-run} {--only=} {--chunk=500}';

    /**
     * The console command description.
     */
    protected $description = 'Normalize WhatsApp phone numbers for teachers and students to 62XXXXXXXXXX format.';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $only = $this->option('only');
        $chunk = (int) $this->option('chunk');
        if ($chunk < 1) {
            $chunk = 500;
        }

        $this->components->info('Starting phone number normalization');
        $this->components->twoColumnDetail('Dry run', $dryRun ? 'yes' : 'no');
        $this->components->twoColumnDetail('Only', $only ?: '(both)');
        $this->components->twoColumnDetail('Chunk size', (string) $chunk);

        $totalScanned = 0;
        $totalChanged = 0;
        $totalInvalid = 0;

        if (!$only || $only === 'teachers') {
            [$scanned, $changed, $invalid] = $this->processTeachers($dryRun, $chunk);
            $totalScanned += $scanned; $totalChanged += $changed; $totalInvalid += $invalid;
        }

        if (!$only || $only === 'students') {
            [$scanned, $changed, $invalid] = $this->processStudents($dryRun, $chunk);
            $totalScanned += $scanned; $totalChanged += $changed; $totalInvalid += $invalid;
        }

        $this->newLine();
        $this->components->info('Normalization summary');
        $this->components->twoColumnDetail('Total scanned', (string) $totalScanned);
        $this->components->twoColumnDetail('Total changed', (string) $totalChanged);
        $this->components->twoColumnDetail('Total set to null (invalid)', (string) $totalInvalid);

        return self::SUCCESS;
    }

    /**
     * @return array{0:int,1:int,2:int} [scanned, changed, invalid]
     */
    protected function processTeachers(bool $dryRun, int $chunk): array
    {
        $this->newLine();
        $this->components->task('Processing teachers.whatsapp_number', function () use ($dryRun, $chunk, &$scanned, &$changed, &$invalid) {
            $scanned = $changed = $invalid = 0;

            Teacher::query()
                ->select(['id', 'name', 'whatsapp_number'])
                ->orderBy('id')
                ->chunkById($chunk, function ($rows) use ($dryRun, &$scanned, &$changed, &$invalid) {
                    foreach ($rows as $row) {
                        $scanned++;
                        $original = $row->whatsapp_number;
                        $normalized = PhoneNumber::normalize($original);

                        if ($normalized !== null) {
                            if ($normalized !== $original) {
                                $changed++;
                                if (!$dryRun) {
                                    Teacher::whereKey($row->id)->update(['whatsapp_number' => $normalized]);
                                }
                            }
                        } else {
                            // invalid after normalization
                            if (!empty($original)) {
                                $invalid++;
                            }
                            if (!$dryRun) {
                                Teacher::whereKey($row->id)->update(['whatsapp_number' => null]);
                            }
                        }
                    }
                });

            return true;
        });

        return [$scanned, $changed, $invalid];
    }

    /**
     * @return array{0:int,1:int,2:int} [scanned, changed, invalid]
     */
    protected function processStudents(bool $dryRun, int $chunk): array
    {
        $this->newLine();
        $this->components->task('Processing students.parent_whatsapp', function () use ($dryRun, $chunk, &$scanned, &$changed, &$invalid) {
            $scanned = $changed = $invalid = 0;

            Student::query()
                ->select(['id', 'name', 'parent_whatsapp'])
                ->orderBy('id')
                ->chunkById($chunk, function ($rows) use ($dryRun, &$scanned, &$changed, &$invalid) {
                    foreach ($rows as $row) {
                        $scanned++;
                        $original = $row->parent_whatsapp;
                        $normalized = PhoneNumber::normalize($original);

                        if ($normalized !== null) {
                            if ($normalized !== $original) {
                                $changed++;
                                if (!$dryRun) {
                                    Student::whereKey($row->id)->update(['parent_whatsapp' => $normalized]);
                                }
                            }
                        } else {
                            // invalid after normalization
                            if (!empty($original)) {
                                $invalid++;
                            }
                            if (!$dryRun) {
                                Student::whereKey($row->id)->update(['parent_whatsapp' => null]);
                            }
                        }
                    }
                });

            return true;
        });

        return [$scanned, $changed, $invalid];
    }
}
