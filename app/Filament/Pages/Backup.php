<?php

namespace App\Filament\Pages;

use App\Models\Backup as BackupModel;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Enums\ActionSize;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Log;

class Backup extends Page
{

    protected static ?string $navigationIcon = 'heroicon-o-circle-stack';
    protected static ?string $navigationLabel = 'Cadangkan & Pulihkan';
    protected static ?string $navigationGroup = 'Pengaturan Sistem';

    protected static string $view = 'filament.pages.backup';

    protected static ?string $title = 'Cadangkan & Pulihkan';

    protected static ?int $navigationSort = 7;

    public bool $isBackingUp = false;
    public string $backupProgress = '';
    public ?int $selectedBackupId = null;

    /**
     * Membuat backup database dan file aplikasi
     */
    public function createBackup()
    {
        try {
            $this->isBackingUp = true;
            $this->backupProgress = 'Memulai proses backup...';

            // Buat direktori backup jika belum ada
            $backupDir = storage_path('app/backups');
            if (!File::exists($backupDir)) {
                File::makeDirectory($backupDir, 0755, true);
            }

            $timestamp = now()->format('Y-m-d_H-i-s');
            $backupFileName = "backup_presensiku_{$timestamp}.sql";
            $backupFilePath = $backupDir . '/' . $backupFileName;

            $this->backupProgress = 'Membuat backup database...';

            // Backup database menggunakan mysqldump
            $this->createDatabaseBackup($backupFilePath);

            $this->backupProgress = 'Menyimpan informasi backup...';

            // Simpan informasi backup ke database
            $fileSize = File::exists($backupFilePath) ? File::size($backupFilePath) : 0;

            BackupModel::create([
                'file_path' => $backupFileName,
                'restored' => false,
                'file_size' => $fileSize,
                'description' => 'Database backup created on ' . now()->format('Y-m-d H:i:s'),
            ]);

            $this->backupProgress = '';
            $this->isBackingUp = false;

            Notification::make()
                ->title('Backup Berhasil!')
                ->body("File backup: {$backupFileName}")
                ->success()
                ->duration(5000)
                ->send();

            // Auto cleanup - hapus backup lama (lebih dari 30 hari)
            $this->cleanupOldBackups();

        } catch (Exception $e) {
            $this->isBackingUp = false;
            $this->backupProgress = '';

            Log::error('Backup failed: ' . $e->getMessage());

            Notification::make()
                ->title('Backup Gagal!')
                ->body('Terjadi kesalahan: ' . $e->getMessage())
                ->danger()
                ->duration(10000)
                ->send();
        }
    }

    /**
     * Membuat backup database menggunakan berbagai metode
     */
    private function createDatabaseBackup(string $filePath)
    {
        // Coba mysqldump terlebih dahulu
        if ($this->tryMysqldump($filePath)) {
            return;
        }

        // Fallback ke Laravel Schema dump
        if ($this->tryLaravelSchemaDump($filePath)) {
            return;
        }

        // Fallback ke custom SQL export
        $this->createCustomSqlBackup($filePath);
    }

    /**
     * Coba backup menggunakan mysqldump
     */
    private function tryMysqldump(string $filePath): bool
    {
        try {
            // Check if mysqldump exists
            $mysqldumpPaths = [
                '/usr/local/bin/mysqldump',
                '/usr/bin/mysqldump',
                '/opt/homebrew/bin/mysqldump',
                'mysqldump' // system PATH
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

            if (!$mysqldumpCmd) {
                return false;
            }

            $dbConfig = config('database.connections.' . config('database.default'));

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
            exec($command . ' 2>&1', $output, $returnCode);

            if ($returnCode === 0 && File::exists($filePath) && File::size($filePath) > 0) {
                return true;
            }

            return false;

        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Coba backup menggunakan Laravel Schema dump dengan prioritas tabel
     */
    private function tryLaravelSchemaDump(string $filePath): bool
    {
        try {
            // Try using Laravel's schema dump if available
            if (class_exists('Illuminate\Database\Schema\Grammars\MySqlGrammar')) {
                // Use Laravel's built-in database dumping
                $connection = DB::connection();
                $schemaBuilder = $connection->getSchemaBuilder();

                // Get all tables
                $allTables = $schemaBuilder->getAllTables();
                $tableNames = array_map(function($table) {
                    return array_values((array)$table)[0];
                }, $allTables);

                // Sort tables by priority (critical tables first)
                $prioritizedTables = $this->prioritizeTables($tableNames);

                $sql = "-- Laravel Database Backup with Prioritized Tables\n";
                $sql .= "-- Generated on: " . now()->format('Y-m-d H:i:s') . "\n\n";
                $sql .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

                foreach ($prioritizedTables as $tableName) {
                    try {
                        // Export table structure
                        $createTable = DB::select("SHOW CREATE TABLE `{$tableName}`")[0];
                        $sql .= "DROP TABLE IF EXISTS `{$tableName}`;\n";
                        $sql .= $createTable->{'Create Table'} . ";\n\n";

                        // Export table data with chunking to handle large tables
                        $totalRows = DB::table($tableName)->count();
                        if ($totalRows > 0) {
                            $chunkSize = 1000; // Process 1000 rows at a time
                            $offset = 0;
                            $totalProcessed = 0;

                            while ($offset < $totalRows) {
                                $rows = DB::table($tableName)->offset($offset)->limit($chunkSize)->get();

                                if ($rows->count() > 0) {
                                    $values = [];
                                    foreach ($rows as $row) {
                                        $rowData = array_map(function($value) {
                                            return is_null($value) ? 'NULL' : "'" . addslashes($value) . "'";
                                        }, (array)$row);
                                        $values[] = '(' . implode(', ', $rowData) . ')';
                                    }

                                    $sql .= "INSERT INTO `{$tableName}` VALUES \n";
                                    $sql .= implode(",\n", $values) . ";\n\n";

                                    $totalProcessed += $rows->count();
                                }

                                $offset += $chunkSize;

                                // Prevent infinite loop
                                if ($rows->count() < $chunkSize) {
                                    break;
                                }
                            }

                            // Log critical table backup with actual processed count
                            if (in_array($tableName, ['users', 'settings'])) {
                                Log::info("Critical table '{$tableName}' backed up with {$totalProcessed} records (total: {$totalRows})");
                            }

                            // Log warning if processed count doesn't match total
                            if ($totalProcessed !== $totalRows) {
                                Log::warning("Table '{$tableName}' backup may be incomplete: processed {$totalProcessed} of {$totalRows} records");
                            }
                        }
                    } catch (Exception $e) {
                        Log::warning("Failed to backup table '{$tableName}': " . $e->getMessage());
                        // Continue with other tables
                    }
                }

                $sql .= "SET FOREIGN_KEY_CHECKS=1;\n";

                File::put($filePath, $sql);

                return File::exists($filePath) && File::size($filePath) > 0;
            }

            return false;

        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Custom SQL backup sebagai fallback terakhir dengan prioritas tabel
     */
    private function createCustomSqlBackup(string $filePath)
    {
        try {
            $sql = "-- Custom Database Backup with Prioritized Tables\n";
            $sql .= "-- Generated on: " . now()->format('Y-m-d H:i:s') . "\n\n";
            $sql .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

            // Get all tables using raw query
            $tables = DB::select('SHOW TABLES');
            $tableNames = array_map(function($table) {
                return array_values((array)$table)[0];
            }, $tables);

            // Sort tables by priority
            $prioritizedTables = $this->prioritizeTables($tableNames);

            foreach ($prioritizedTables as $tableName) {
                try {
                    // Get table structure
                    $createTable = DB::select("SHOW CREATE TABLE `{$tableName}`");
                    if (!empty($createTable)) {
                        $sql .= "DROP TABLE IF EXISTS `{$tableName}`;\n";
                        $sql .= $createTable[0]->{'Create Table'} . ";\n\n";
                    }

                    // Get table data with chunking to handle large tables
                    $totalRows = DB::table($tableName)->count();
                    if ($totalRows > 0) {
                        $chunkSize = 1000; // Process 1000 rows at a time
                        $offset = 0;
                        $totalProcessed = 0;

                        while ($offset < $totalRows) {
                            $rows = DB::table($tableName)->offset($offset)->limit($chunkSize)->get();

                            if ($rows->count() > 0) {
                                $values = [];

                                foreach ($rows as $row) {
                                    $rowData = [];
                                    foreach ((array)$row as $value) {
                                        if (is_null($value)) {
                                            $rowData[] = 'NULL';
                                        } else {
                                            $rowData[] = "'" . str_replace(["'", "\\"], ["\\'", "\\\\"], $value) . "'";
                                        }
                                    }
                                    $values[] = '(' . implode(', ', $rowData) . ')';
                                }

                                // Split into smaller chunks for INSERT statements
                                $insertChunks = array_chunk($values, 100);
                                foreach ($insertChunks as $chunk) {
                                    $sql .= "INSERT INTO `{$tableName}` VALUES \n";
                                    $sql .= implode(",\n", $chunk) . ";\n\n";
                                }

                                $totalProcessed += $rows->count();
                            }

                            $offset += $chunkSize;

                            // Prevent infinite loop
                            if ($rows->count() < $chunkSize) {
                                break;
                            }
                        }

                        // Log critical table backup with actual processed count
                        if (in_array($tableName, ['users', 'settings'])) {
                            Log::info("Critical table '{$tableName}' backed up with {$totalProcessed} records (total: {$totalRows})");
                        }

                        // Log warning if processed count doesn't match total
                        if ($totalProcessed !== $totalRows) {
                            Log::warning("Table '{$tableName}' backup may be incomplete: processed {$totalProcessed} of {$totalRows} records");
                        }
                    }

                } catch (Exception $e) {
                    // Skip table if error occurs but log it
                    $sql .= "-- Error backing up table {$tableName}: " . $e->getMessage() . "\n\n";
                    Log::warning("Failed to backup table '{$tableName}': " . $e->getMessage());
                }
            }

            $sql .= "SET FOREIGN_KEY_CHECKS=1;\n";

            File::put($filePath, $sql);

            if (!File::exists($filePath) || File::size($filePath) === 0) {
                throw new Exception('Custom backup file tidak berhasil dibuat atau kosong');
            }

        } catch (Exception $e) {
            throw new Exception('Semua metode backup gagal: ' . $e->getMessage());
        }
    }

    /**
     * Prioritize tables untuk backup/restore (critical tables first)
     */
    private function prioritizeTables(array $tableNames): array
    {
        // Define critical tables that should be backed up and restored first
        $criticalTables = [
            'migrations',     // Laravel migrations (structure)
            'users',          // User accounts
            'settings',       // Application settings
            'roles',          // User roles
            'permissions',    // User permissions
            'model_has_roles',
            'model_has_permissions',
            'role_has_permissions',
        ];

        // Tables that should be processed last (large data tables)
        $largeTables = [
            'student_attendances',
            'student_leave_requests',
            'teacher_attendances',
            'scan_logs',
            'notifications',
            'sessions',
            'cache',
            'jobs',
            'failed_jobs',
        ];

        $prioritized = [];
        $remaining = [];
        $large = [];

        // First, add critical tables in order
        foreach ($criticalTables as $criticalTable) {
            if (in_array($criticalTable, $tableNames)) {
                $prioritized[] = $criticalTable;
            }
        }

        // Then add remaining tables (excluding large ones)
        foreach ($tableNames as $tableName) {
            if (!in_array($tableName, $criticalTables) && !in_array($tableName, $largeTables)) {
                $remaining[] = $tableName;
            }
        }

        // Finally add large tables
        foreach ($largeTables as $largeTable) {
            if (in_array($largeTable, $tableNames)) {
                $large[] = $largeTable;
            }
        }

        // Combine all arrays
        return array_merge($prioritized, $remaining, $large);
    }

    /**
     * Restore database dari file backup
     */
    public function restoreDatabase(string $fileName)
    {
        try {
            $backupFilePath = storage_path('app/backups/' . $fileName);

            if (!File::exists($backupFilePath)) {
                throw new Exception('File backup tidak ditemukan');
            }

            $this->backupProgress = 'Memulai proses restore...';

            // Try mysql command first
            if ($this->tryMysqlRestore($backupFilePath)) {
                $this->finishRestore($fileName, 'MySQL command');
                return;
            }

            // Fallback to PHP-based restore
            $this->restoreUsingPhp($backupFilePath);
            $this->finishRestore($fileName, 'PHP method');

        } catch (Exception $e) {
            $this->backupProgress = '';

            Log::error('Restore failed: ' . $e->getMessage());

            Notification::make()
                ->title('Restore Gagal!')
                ->body('Terjadi kesalahan: ' . $e->getMessage())
                ->danger()
                ->duration(10000)
                ->send();
        }
    }

    /**
     * Coba restore menggunakan mysql command
     */
    private function tryMysqlRestore(string $backupFilePath): bool
    {
        try {
            // Check if mysql exists
            $mysqlPaths = [
                '/usr/local/bin/mysql',
                '/usr/bin/mysql',
                '/opt/homebrew/bin/mysql',
                'mysql' // system PATH
            ];

            $mysqlCmd = null;
            foreach ($mysqlPaths as $path) {
                if ($path === 'mysql') {
                    // Check if it's in PATH
                    exec('which mysql 2>/dev/null', $output, $returnCode);
                    if ($returnCode === 0) {
                        $mysqlCmd = 'mysql';
                        break;
                    }
                } else {
                    if (file_exists($path)) {
                        $mysqlCmd = $path;
                        break;
                    }
                }
            }

            if (!$mysqlCmd) {
                return false;
            }

            $dbConfig = config('database.connections.' . config('database.default'));

            $host = $dbConfig['host'];
            $port = $dbConfig['port'];
            $database = $dbConfig['database'];
            $username = $dbConfig['username'];
            $password = $dbConfig['password'];

            // Command mysql restore
            $command = sprintf(
                '%s --host=%s --port=%s --user=%s --password=%s %s < %s',
                $mysqlCmd,
                escapeshellarg($host),
                escapeshellarg($port),
                escapeshellarg($username),
                escapeshellarg($password),
                escapeshellarg($database),
                escapeshellarg($backupFilePath)
            );

            $output = [];
            $returnCode = 0;
            exec($command . ' 2>&1', $output, $returnCode);

            return $returnCode === 0;

        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Restore menggunakan PHP dan Laravel DB
     */
    private function restoreUsingPhp(string $backupFilePath)
    {
        $this->backupProgress = 'Membaca file backup...';

        // Read the SQL file
        $sql = File::get($backupFilePath);

        if (empty($sql)) {
            throw new Exception('File backup kosong atau tidak dapat dibaca');
        }

        $this->backupProgress = 'Memproses SQL statements...';

        // Split SQL into individual statements
        $statements = $this->parseSqlStatements($sql);

        if (empty($statements)) {
            throw new Exception('Tidak ada SQL statement yang valid ditemukan dalam file backup');
        }

        $this->backupProgress = 'Menjalankan restore database...';

        // Disable foreign key checks during restore
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        $totalStatements = count($statements);
        $processedStatements = 0;
        $failedStatements = 0;

        try {
            // Execute each statement
            foreach ($statements as $statement) {
                $statement = trim($statement);

                if (empty($statement) || str_starts_with($statement, '--')) {
                    continue;
                }

                try {
                    DB::statement($statement);
                    $processedStatements++;

                    // Update progress periodically
                    if ($processedStatements % 25 === 0) {
                        $percentage = round(($processedStatements / $totalStatements) * 100);
                        $this->backupProgress = "Restore progress: {$percentage}% ({$processedStatements}/{$totalStatements})";
                    }

                    // Log critical tables
                    if (str_contains($statement, 'INSERT INTO `users`') || str_contains($statement, 'INSERT INTO `settings`')) {
                        Log::info('Critical table data restored', [
                            'statement_type' => str_contains($statement, 'users') ? 'users' : 'settings',
                            'statement_preview' => substr($statement, 0, 100) . '...'
                        ]);
                    }

                } catch (Exception $e) {
                    $failedStatements++;

                    // Log individual statement errors but continue
                    Log::warning('Failed to execute SQL statement during restore', [
                        'statement' => substr($statement, 0, 200) . '...',
                        'error' => $e->getMessage()
                    ]);

                    // If it's a critical table, log as error but don't throw (data might still be restored)
                    if (str_contains($statement, 'CREATE TABLE `users`') ||
                        str_contains($statement, 'CREATE TABLE `settings`') ||
                        str_contains($statement, 'INSERT INTO `users`') ||
                        str_contains($statement, 'INSERT INTO `settings`')) {
                        Log::error('Critical table restore failed', [
                            'table' => str_contains($statement, 'users') ? 'users' : 'settings',
                            'statement' => substr($statement, 0, 300),
                            'error' => $e->getMessage()
                        ]);
                    }
                }
            }

        } finally {
            // Re-enable foreign key checks
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }

        if ($processedStatements === 0) {
            throw new Exception('Tidak ada SQL statement yang berhasil dijalankan');
        }

        // Verify critical tables were restored
        $this->verifyCriticalTablesRestored();

        Log::info("Restore completed: {$processedStatements}/{$totalStatements} statements executed, {$failedStatements} failed");
    }

    /**
     * Verify that critical tables (users, settings) were restored properly
     */
    private function verifyCriticalTablesRestored()
    {
        try {
            $userCount = DB::table('users')->count();
            $settingsCount = DB::table('settings')->count();

            Log::info('Critical tables verification', [
                'users_count' => $userCount,
                'settings_count' => $settingsCount
            ]);

            if ($userCount === 0) {
                Log::warning('Users table is empty after restore - this might indicate an issue');
            }

            if ($settingsCount === 0) {
                Log::warning('Settings table is empty after restore - this might indicate an issue');
            }

        } catch (Exception $e) {
            Log::error('Failed to verify critical tables after restore', [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Parse SQL file into individual statements
     */
    private function parseSqlStatements(string $sql): array
    {
        // Remove comments and empty lines
        $lines = explode("\n", $sql);
        $cleanedSql = '';

        foreach ($lines as $line) {
            $line = trim($line);

            // Skip comments and empty lines
            if (empty($line) || str_starts_with($line, '--') || str_starts_with($line, '/*')) {
                continue;
            }

            $cleanedSql .= $line . "\n";
        }

        // Split by semicolon but be careful with semicolons inside strings
        $statements = [];
        $currentStatement = '';
        $inString = false;
        $stringDelimiter = null;

        for ($i = 0; $i < strlen($cleanedSql); $i++) {
            $char = $cleanedSql[$i];

            if (!$inString && ($char === '"' || $char === "'")) {
                $inString = true;
                $stringDelimiter = $char;
            } elseif ($inString && $char === $stringDelimiter) {
                // Check if it's escaped
                if ($i > 0 && $cleanedSql[$i - 1] !== '\\') {
                    $inString = false;
                    $stringDelimiter = null;
                }
            }

            $currentStatement .= $char;

            // If we hit a semicolon outside of a string, that's the end of a statement
            if (!$inString && $char === ';') {
                $statement = trim($currentStatement);
                if (!empty($statement)) {
                    $statements[] = $statement;
                }
                $currentStatement = '';
            }
        }

        // Add any remaining statement
        $statement = trim($currentStatement);
        if (!empty($statement)) {
            $statements[] = $statement;
        }

        return $statements;
    }

    /**
     * Finish restore process
     */
    private function finishRestore(string $fileName, string $method)
    {
        // Update status backup sebagai restored
        BackupModel::where('file_path', $fileName)->update(['restored' => true]);

        $this->backupProgress = '';

        Notification::make()
            ->title('Restore Berhasil!')
            ->body("Database berhasil dipulihkan dari: {$fileName} (menggunakan {$method})")
            ->success()
            ->duration(5000)
            ->send();

        Log::info("Database restore completed successfully using {$method}", [
            'file_name' => $fileName,
            'method' => $method
        ]);
    }

    /**
     * Download file backup
     */
    public function downloadBackup(string $fileName)
    {
        $backupFilePath = storage_path('app/backups/' . $fileName);

        if (!File::exists($backupFilePath)) {
            Notification::make()
                ->title('File Tidak Ditemukan!')
                ->body('File backup tidak ditemukan')
                ->danger()
                ->send();
            return;
        }

        return response()->download($backupFilePath, $fileName);
    }

    /**
     * Hapus file backup dengan konfirmasi
     */
    public function deleteBackup(int $backupId)
    {
        try {
            $backup = BackupModel::findOrFail($backupId);
            $backupFilePath = storage_path('app/backups/' . $backup->file_path);

            // Hapus file fisik
            if (File::exists($backupFilePath)) {
                File::delete($backupFilePath);
            }

            // Hapus record dari database
            $backup->delete();

            Notification::make()
                ->title('Backup Dihapus!')
                ->body('File backup berhasil dihapus')
                ->success()
                ->send();

        } catch (Exception $e) {
            Log::error('Delete backup failed: ' . $e->getMessage());

            Notification::make()
                ->title('Gagal Menghapus!')
                ->body('Terjadi kesalahan saat menghapus backup')
                ->danger()
                ->send();
        }
    }

    /**
     * Restore database dengan konfirmasi
     */
    public function confirmRestoreDatabase(string $fileName)
    {
        $this->restoreDatabase($fileName);
    }

    /**
     * Bersihkan backup lama (lebih dari 30 hari)
     */
    private function cleanupOldBackups()
    {
        try {
            $oldBackups = BackupModel::where('created_at', '<', Carbon::now()->subDays(30))->get();

            foreach ($oldBackups as $backup) {
                $backupFilePath = storage_path('app/backups/' . $backup->file_path);

                if (File::exists($backupFilePath)) {
                    File::delete($backupFilePath);
                }

                $backup->delete();
            }

            if ($oldBackups->count() > 0) {
                Log::info("Cleaned up {$oldBackups->count()} old backup files");
            }

        } catch (Exception $e) {
            Log::error('Cleanup old backups failed: ' . $e->getMessage());
        }
    }

    /**
     * Mengambil daftar file backup
     */
    public function getBackups()
    {
        return BackupModel::orderBy('created_at', 'desc')->get()->map(function ($backup) {
            $filePath = storage_path('app/backups/' . $backup->file_path);
            $fileExists = File::exists($filePath);
            $fileSize = $fileExists ? $this->formatBytes(File::size($filePath)) : 'N/A';

            return (object) [
                'id' => $backup->id,
                'file_path' => $backup->file_path,
                'created_at' => $backup->created_at,
                'restored' => $backup->restored,
                'file_exists' => $fileExists,
                'file_size' => $fileSize,
            ];
        });
    }

    /**
     * Format bytes ke format yang mudah dibaca
     */
    private function formatBytes($bytes, $precision = 2)
    {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision) . ' ' . $units[$i];
    }

    /**
     * Cek status sistem backup
     */
    public function getBackupStatus()
    {
        $backupDir = storage_path('app/backups');
        $totalBackups = BackupModel::count();
        $latestBackup = BackupModel::latest()->first();
        $diskSpace = $this->getDiskSpace($backupDir);

        return [
            'total_backups' => $totalBackups,
            'latest_backup' => $latestBackup?->created_at?->diffForHumans(),
            'backup_directory_exists' => File::exists($backupDir),
            'disk_space' => $diskSpace,
        ];
    }

    /**
     * Mendapatkan informasi disk space
     */
    private function getDiskSpace($directory)
    {
        if (!File::exists($directory)) {
            return null;
        }

        $bytes = disk_free_space($directory);
        return $this->formatBytes($bytes);
    }
}
