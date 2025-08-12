<?php

namespace App\Console\Commands;

use App\Models\Backup as BackupModel;
use Carbon\Carbon;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

/**
 * Create a database backup file and optionally clean up old backups.
 *
 * Options:
 * - --cleanup  Remove old backups after creating a new one
 * - --days     Number of days to keep backups (default: 30)
 */
class BackupDatabase extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backup:database 
                           {--cleanup : Cleanup old backups after creating new one}
                           {--days=30 : Number of days to keep backups (default: 30)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a database backup';

    /**
     * Execute the console command.
     *
     * Creates a backup file using one of several strategies (mysqldump,
     * Laravel schema dump, or custom SQL export). Records the backup metadata
     * in the `backups` table. Optionally cleans up old backups.
     *
     * @return int Command exit code
     */
    public function handle()
    {
        try {
            $this->info('Starting database backup...');

            // Buat direktori backup jika belum ada
            $backupDir = storage_path('app/backups');
            if (! File::exists($backupDir)) {
                File::makeDirectory($backupDir, 0755, true);
                $this->info('Created backup directory: '.$backupDir);
            }

            $timestamp = now()->format('Y-m-d_H-i-s');
            $backupFileName = "backup_presensiku_{$timestamp}.sql";
            $backupFilePath = $backupDir.'/'.$backupFileName;

            $this->info('Creating database backup: '.$backupFileName);

            // Backup database menggunakan mysqldump
            $this->createDatabaseBackup($backupFilePath);

            $this->info('Saving backup information to database...');

            // Simpan informasi backup ke database
            $fileSize = File::exists($backupFilePath) ? File::size($backupFilePath) : 0;

            BackupModel::create([
                'file_path' => $backupFileName,
                'restored' => false,
                'file_size' => $fileSize,
                'description' => 'Automatic database backup created via command on '.now()->format('Y-m-d H:i:s'),
            ]);

            $this->info('✅ Backup completed successfully!');
            $this->info('File: '.$backupFileName);
            $this->info('Size: '.$this->formatBytes($fileSize));

            // Cleanup old backups if requested
            if ($this->option('cleanup')) {
                $days = (int) $this->option('days');
                $this->cleanupOldBackups($days);
            }

            Log::info('Database backup completed successfully', [
                'file_name' => $backupFileName,
                'file_size' => $fileSize,
                'file_path' => $backupFilePath,
            ]);

            return Command::SUCCESS;

        } catch (Exception $e) {
            $this->error('❌ Backup failed: '.$e->getMessage());

            Log::error('Database backup failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return Command::FAILURE;
        }
    }

    /**
     * Create a database backup using multiple possible strategies.
     *
     * Tries mysqldump first, then Laravel schema dump, and finally a custom
     * SQL export as the last fallback.
     *
     * @param string $filePath Absolute path to write the backup file
     * @return void
     */
    private function createDatabaseBackup(string $filePath)
    {
        $this->info('Trying mysqldump...');
        // Coba mysqldump terlebih dahulu
        if ($this->tryMysqldump($filePath)) {
            $this->info('✅ Backup created using mysqldump');

            return;
        }

        $this->info('Mysqldump not available, trying Laravel schema dump...');
        // Fallback ke Laravel Schema dump
        if ($this->tryLaravelSchemaDump($filePath)) {
            $this->info('✅ Backup created using Laravel schema dump');

            return;
        }

        $this->info('Trying custom SQL backup...');
        // Fallback ke custom SQL export
        $this->createCustomSqlBackup($filePath);
        $this->info('✅ Backup created using custom SQL method');
    }

    /**
     * Attempt backup using mysqldump if available on the system.
     *
     * @param string $filePath Absolute path to write the backup file
     * @return bool True on success
     */
    private function tryMysqldump(string $filePath): bool
    {
        try {
            // Check if mysqldump exists
            $mysqldumpPaths = [
                '/usr/local/bin/mysqldump',
                '/usr/bin/mysqldump',
                '/opt/homebrew/bin/mysqldump',
                'mysqldump', // system PATH
            ];

            $mysqldumpCmd = null;
            foreach ($mysqldumpPaths as $path) {
                if ($path === 'mysqldump') {
                    // Check if it's in PATH
                    exec('which mysqldump 2>/dev/null', $output, $returnCode);
                    if ($returnCode === 0) {
                        $mysqldumpCmd = 'mysqldump';
                        break;
                    }
                } else {
                    if (file_exists($path)) {
                        $mysqldumpCmd = $path;
                        break;
                    }
                }
            }

            if (! $mysqldumpCmd) {
                return false;
            }

            $dbConfig = config('database.connections.'.config('database.default'));

            $host = $dbConfig['host'];
            $port = $dbConfig['port'];
            $database = $dbConfig['database'];
            $username = $dbConfig['username'];
            $password = $dbConfig['password'];

            // Command mysqldump
            $command = sprintf(
                '%s --host=%s --port=%s --user=%s --password=%s --single-transaction --routines --triggers %s > %s',
                $mysqldumpCmd,
                escapeshellarg($host),
                escapeshellarg($port),
                escapeshellarg($username),
                escapeshellarg($password),
                escapeshellarg($database),
                escapeshellarg($filePath)
            );

            $output = [];
            $returnCode = 0;
            exec($command.' 2>&1', $output, $returnCode);

            if ($returnCode === 0 && File::exists($filePath) && File::size($filePath) > 0) {
                return true;
            }

            return false;

        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Attempt backup using a simple Laravel schema and data dump.
     *
     * @param string $filePath Absolute path to write the backup file
     * @return bool True on success
     */
    private function tryLaravelSchemaDump(string $filePath): bool
    {
        try {
            $sql = "-- Laravel Database Backup\n";
            $sql .= '-- Generated on: '.now()->format('Y-m-d H:i:s')."\n\n";
            $sql .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

            // Get all tables using raw query
            $tables = \DB::select('SHOW TABLES');

            foreach ($tables as $table) {
                $tableName = array_values((array) $table)[0];

                // Export table structure
                $createTable = \DB::select("SHOW CREATE TABLE `{$tableName}`")[0];
                $sql .= "DROP TABLE IF EXISTS `{$tableName}`;\n";
                $sql .= $createTable->{'Create Table'}.";\n\n";

                // Export table data
                $rows = \DB::table($tableName)->get();
                if ($rows->count() > 0) {
                    $sql .= "INSERT INTO `{$tableName}` VALUES \n";
                    $values = [];
                    foreach ($rows as $row) {
                        $rowData = array_map(function ($value) {
                            return is_null($value) ? 'NULL' : "'".addslashes($value)."'";
                        }, (array) $row);
                        $values[] = '('.implode(', ', $rowData).')';
                    }
                    $sql .= implode(",\n", $values).";\n\n";
                }
            }

            $sql .= "SET FOREIGN_KEY_CHECKS=1;\n";

            File::put($filePath, $sql);

            return File::exists($filePath) && File::size($filePath) > 0;

        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Perform a custom SQL backup as the final fallback.
     *
     * @param string $filePath Absolute path to write the backup file
     * @return void
     * @throws Exception If the output file cannot be created or is empty
     */
    private function createCustomSqlBackup(string $filePath)
    {
        $sql = "-- Custom Database Backup\n";
        $sql .= '-- Generated on: '.now()->format('Y-m-d H:i:s')."\n\n";
        $sql .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

        // Get all tables using raw query
        $tables = \DB::select('SHOW TABLES');

        foreach ($tables as $table) {
            $tableName = array_values((array) $table)[0];

            try {
                // Get table structure
                $createTable = \DB::select("SHOW CREATE TABLE `{$tableName}`");
                if (! empty($createTable)) {
                    $sql .= "DROP TABLE IF EXISTS `{$tableName}`;\n";
                    $sql .= $createTable[0]->{'Create Table'}.";\n\n";
                }

                // Get table data
                $rows = \DB::table($tableName)->get();
                if ($rows->count() > 0) {
                    $values = [];

                    foreach ($rows as $row) {
                        $rowData = [];
                        foreach ((array) $row as $value) {
                            if (is_null($value)) {
                                $rowData[] = 'NULL';
                            } else {
                                $rowData[] = "'".str_replace(["'", '\\'], ["\\'", '\\\\'], $value)."'";
                            }
                        }
                        $values[] = '('.implode(', ', $rowData).')';
                    }

                    // Split into chunks to avoid memory issues
                    $chunks = array_chunk($values, 100);
                    foreach ($chunks as $chunk) {
                        $sql .= "INSERT INTO `{$tableName}` VALUES \n";
                        $sql .= implode(",\n", $chunk).";\n\n";
                    }
                }

            } catch (Exception $e) {
                // Skip table if error occurs
                $sql .= "-- Error backing up table {$tableName}: ".$e->getMessage()."\n\n";
            }
        }

        $sql .= "SET FOREIGN_KEY_CHECKS=1;\n";

        File::put($filePath, $sql);

        if (! File::exists($filePath) || File::size($filePath) === 0) {
            throw new Exception('Custom backup file tidak berhasil dibuat atau kosong');
        }
    }

    /**
     * Remove backup records and files older than the specified number of days.
     *
     * @param int $days Number of days to retain backups
     * @return void
     */
    private function cleanupOldBackups(int $days = 30)
    {
        try {
            $this->info("Cleaning up backups older than {$days} days...");

            $oldBackups = BackupModel::where('created_at', '<', Carbon::now()->subDays($days))->get();

            $deletedCount = 0;
            foreach ($oldBackups as $backup) {
                $backupFilePath = storage_path('app/backups/'.$backup->file_path);

                if (File::exists($backupFilePath)) {
                    File::delete($backupFilePath);
                }

                $backup->delete();
                $deletedCount++;
            }

            if ($deletedCount > 0) {
                $this->info("✅ Cleaned up {$deletedCount} old backup files");
                Log::info("Cleaned up {$deletedCount} old backup files");
            } else {
                $this->info('No old backups to clean up');
            }

        } catch (Exception $e) {
            $this->error('Failed to cleanup old backups: '.$e->getMessage());
            Log::error('Cleanup old backups failed: '.$e->getMessage());
        }
    }

    /**
     * Convert a byte size into a human-readable string (e.g., MB, GB).
     *
     * @param int|float $bytes Raw byte size
     * @param int $precision Decimal precision
     * @return string Human-readable size
     */
    private function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision).' '.$units[$i];
    }
}
