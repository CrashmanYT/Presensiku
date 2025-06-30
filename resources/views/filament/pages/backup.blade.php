<x-filament-panels::page>
    <x-filament::card>
        <h2 class="text-lg font-semibold mb-4">Backup Database</h2>
        <p class="mb-4">Klik tombol di bawah untuk membuat backup database dan file.</p>
        <x-filament::button wire:click="backupDatabase">
            Buat Backup Sekarang
        </x-filament::button>
    </x-filament::card>

    <x-filament::card class="mt-6">
        <h2 class="text-lg font-semibold mb-4">Daftar File Backup</h2>
        {{-- Tabel untuk menampilkan daftar file backup --}}
        {{-- Contoh struktur tabel --}}
        <x-filament-tables::container>
            <x-filament-tables::table>
                <thead>
                    <tr>
                        <x-filament-tables::header-cell>Tanggal</x-filament-tables::header-cell>
                        <x-filament-tables::header-cell>Nama File</x-filament-tables::header-cell>
                        <x-filament-tables::header-cell>Aksi</x-filament-tables::header-cell>
                    </tr>
                </thead>
                <tbody>
                    @foreach($this->getBackups() as $backup)
                        <x-filament-tables::row>
                            <x-filament-tables::cell>{{ $backup->created_at->format('Y-m-d H:i') }}</x-filament-tables::cell>
                            <x-filament-tables::cell>{{ $backup->file_path }}</x-filament-tables::cell>
                            <x-filament-tables::cell>
                                <x-filament::button wire:click="restoreDatabase('{{ $backup->file_path }}')">
                                    Restore
                                </x-filament::button>
                            </x-filament-tables::cell>
                        </x-filament-tables::row>
                    @endforeach
                </tbody>
            </x-filament-tables::table>
        </x-filament-tables::container>
        
    </x-filament::card>
</x-filament-panels::page>