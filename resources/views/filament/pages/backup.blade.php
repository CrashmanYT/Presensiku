<x-filament-panels::page>
    {{-- Status Sistem Backup --}}
    @php
        $status = $this->getBackupStatus();
    @endphp
    
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
        <x-filament::card class="p-6">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-3xl font-bold text-primary-600">{{ $status['total_backups'] }}</div>
                    <div class="text-sm text-gray-600 mt-1">Total Backup</div>
                </div>
                <div class="p-3 bg-primary-100 rounded-full">
                    <x-heroicon-o-archive-box class="w-6 h-6 text-primary-600" />
                </div>
            </div>
        </x-filament::card>
        
        <x-filament::card class="p-6">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-sm font-medium text-gray-900">
                        {{ $status['latest_backup'] ?? 'Belum ada backup' }}
                    </div>
                    <div class="text-sm text-gray-600 mt-1">Backup Terakhir</div>
                </div>
                <div class="p-3 bg-gray-100 rounded-full">
                    <x-heroicon-o-clock class="w-6 h-6 text-gray-600" />
                </div>
            </div>
        </x-filament::card>
        
        <x-filament::card class="p-6">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-sm font-medium text-gray-900">
                        {{ $status['disk_space'] ?? 'N/A' }}
                    </div>
                    <div class="text-sm text-gray-600 mt-1">Ruang Kosong</div>
                </div>
                <div class="p-3 bg-green-100 rounded-full">
                    <x-heroicon-o-circle-stack class="w-6 h-6 text-green-600" />
                </div>
            </div>
        </x-filament::card>
        
        <x-filament::card class="p-6">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-sm font-medium {{ $status['backup_directory_exists'] ? 'text-green-600' : 'text-red-600' }}">
                        {{ $status['backup_directory_exists'] ? 'Siap' : 'Error' }}
                    </div>
                    <div class="text-sm text-gray-600 mt-1">Status Direktori</div>
                </div>
                <div class="p-3 {{ $status['backup_directory_exists'] ? 'bg-green-100' : 'bg-red-100' }} rounded-full">
                    @if($status['backup_directory_exists'])
                        <x-heroicon-o-check-circle class="w-6 h-6 text-green-600" />
                    @else
                        <x-heroicon-o-x-circle class="w-6 h-6 text-red-600" />
                    @endif
                </div>
            </div>
        </x-filament::card>
    </div>

    {{-- Form Backup --}}
    <x-filament::card>
        <div class="flex items-center justify-between mb-4">
            <div>
                <h2 class="text-lg font-semibold">Buat Backup Database</h2>
                <p class="text-sm text-gray-600 mt-1">
                    Backup akan mencakup seluruh database aplikasi dan disimpan dalam format SQL.
                </p>
            </div>
            <x-heroicon-o-archive-box class="w-8 h-8 text-gray-400" />
        </div>
        
        @if($isBackingUp)
            <div class="mb-4 p-4 bg-blue-50 border border-blue-200 rounded-md">
                <div class="flex items-center">
                    <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <div>
                        <div class="text-sm font-medium text-blue-800">Sedang membuat backup...</div>
                        @if($backupProgress)
                            <div class="text-xs text-blue-600 mt-1">{{ $backupProgress }}</div>
                        @endif
                    </div>
                </div>
            </div>
        @endif
        
        <div class="flex gap-3">
            <x-filament::button 
                wire:click="createBackup" 
                :disabled="$isBackingUp"
                color="primary"
                icon="heroicon-o-plus"
            >
                {{ $isBackingUp ? 'Membuat Backup...' : 'Buat Backup Sekarang' }}
            </x-filament::button>
            
            <x-filament::button 
                wire:click="$refresh" 
                color="gray"
                icon="heroicon-o-arrow-path"
            >
                Refresh
            </x-filament::button>
        </div>
    </x-filament::card>

    {{-- Daftar File Backup --}}
    <x-filament::card class="mt-6">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h2 class="text-lg font-semibold">Daftar File Backup</h2>
                <p class="text-sm text-gray-600 mt-1">
                    Kelola dan pulihkan database dari file backup yang tersimpan.
                </p>
            </div>
            <x-heroicon-o-folder class="w-6 h-6 text-gray-400" />
        </div>
        
        @php
            $backups = $this->getBackups();
        @endphp
        
        @if($backups->count() > 0)
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Tanggal & Waktu
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Nama File
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Ukuran
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Status
                            </th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Aksi
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($backups as $backup)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <div>
                                        <div class="font-medium">{{ $backup->created_at->format('d M Y') }}</div>
                                        <div class="text-gray-500">{{ $backup->created_at->format('H:i:s') }}</div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">{{ $backup->file_path }}</div>
                                    <div class="text-sm text-gray-500">{{ $backup->created_at->diffForHumans() }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ $backup->file_size }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center gap-2">
                                        @if($backup->file_exists)
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                <x-heroicon-s-check-circle class="w-3 h-3 mr-1" />
                                                Tersedia
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                                <x-heroicon-s-x-circle class="w-3 h-3 mr-1" />
                                                Hilang
                                            </span>
                                        @endif
                                        
                                        @if($backup->restored)
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                <x-heroicon-s-arrow-path class="w-3 h-3 mr-1" />
                                                Dipulihkan
                                            </span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <div class="flex items-center justify-end gap-2">
                                        @if($backup->file_exists)
                                            <x-filament::button 
                                                wire:click="downloadBackup('{{ $backup->file_path }}')"
                                                color="gray"
                                                size="sm"
                                                icon="heroicon-o-arrow-down-tray"
                                                tooltip="Download Backup"
                                            >
                                            </x-filament::button>
                                            
                                            <x-filament::button 
                                                wire:click="confirmRestoreDatabase('{{ $backup->file_path }}')"
                                                color="warning"
                                                size="sm"
                                                icon="heroicon-o-arrow-path"
                                                tooltip="Restore Database"
                                                wire:confirm="⚠️ PERINGATAN RESTORE DATABASE ⚠️\n\nAnda akan memulihkan database dari backup: {{ $backup->file_path }}\n\n🔥 TINDAKAN INI AKAN:\n• Mengganti SEMUA data yang ada dalam database\n• Menghapus semua data terbaru yang belum di-backup\n• Membutuhkan waktu beberapa menit untuk selesai\n\n⛔ PASTIKAN:\n• Tidak ada user lain yang sedang menggunakan aplikasi\n• Anda sudah membuat backup terbaru jika diperlukan\n• Aplikasi dalam maintenance mode jika memungkinkan\n\nApakah Anda YAKIN ingin melanjutkan restore?"
                                            >
                                            </x-filament::button>
                                        @endif
                                        
                                        <x-filament::button 
                                            wire:click="deleteBackup({{ $backup->id }})"
                                            color="danger"
                                            size="sm"
                                            icon="heroicon-o-trash"
                                            tooltip="Hapus Backup"
                                            wire:confirm="🗑️ KONFIRMASI HAPUS BACKUP\n\nAnda akan menghapus file backup: {{ $backup->file_path }}\n\n❌ TINDAKAN INI:\n• Tidak dapat dibatalkan\n• File backup akan hilang permanen\n• Data backup tidak dapat dipulihkan setelah dihapus\n\n📅 File dibuat: {{ $backup->created_at->format('d M Y, H:i') }} ({{ $backup->created_at->diffForHumans() }})\n💾 Ukuran file: {{ $backup->file_size }}\n\nApakah Anda yakin ingin menghapus backup ini?"
                                        >
                                        </x-filament::button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="text-center py-12">
                <x-heroicon-o-archive-box class="mx-auto h-16 w-16 text-gray-400" />
                <h3 class="mt-2 text-sm font-medium text-gray-900">Belum ada backup</h3>
                <p class="mt-1 text-sm text-gray-500">Mulai dengan membuat backup pertama Anda.</p>
                <div class="mt-6">
                    <x-filament::button 
                        wire:click="createBackup" 
                        color="primary"
                        icon="heroicon-o-plus"
                    >
                        Buat Backup Pertama
                    </x-filament::button>
                </div>
            </div>
        @endif
    </x-filament::card>

    {{-- Informasi Penting --}}
    <x-filament::card class="mt-6">
        <div class="rounded-md bg-yellow-50 p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <x-heroicon-o-exclamation-triangle class="h-5 w-5 text-yellow-400" />
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-yellow-800">
                        Penting untuk Diketahui
                    </h3>
                    <div class="mt-2 text-sm text-yellow-700">
                        <ul class="list-disc list-inside space-y-1">
                            <li>Backup otomatis akan menghapus file yang lebih lama dari 30 hari</li>
                            <li>Proses restore akan mengganti seluruh data database yang ada</li>
                            <li>Pastikan aplikasi tidak sedang digunakan saat melakukan restore</li>
                            <li>File backup disimpan di direktori: <code class="bg-yellow-100 px-1 rounded">storage/app/backups/</code></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </x-filament::card>
</x-filament-panels::page>
