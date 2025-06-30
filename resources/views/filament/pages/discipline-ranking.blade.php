<x-filament-panels::page>
    <x-filament::card>
        <h2 class="text-lg font-semibold mb-4">Top Siswa Paling Rajin</h2>
        <x-filament-tables::container>
            <x-filament-tables::table>
                <thead>
                    <tr>
                        <x-filament-tables::header-cell>Rank</x-filament-tables::header-cell>
                        <x-filament-tables::header-cell>Nama Siswa</x-filament-tables::header-cell>
                        <x-filament-tables::header-cell>Kelas</x-filament-tables::header-cell>
                        <x-filament-tables::header-cell>Total Hadir</x-filament-tables::header-cell>
                        <x-filament-tables::header-cell>Skor Disiplin</x-filament-tables::header-cell>
                    </tr>
                </thead>
                <tbody>
                    @foreach($this->getTopStudentsByDiscipline() as $index => $ranking)
                        <x-filament-tables::row>
                            <x-filament-tables::cell>{{ $index + 1 }}</x-filament-tables::cell>
                            <x-filament-tables::cell>{{ $ranking->student->name }}</x-filament-tables::cell>
                            <x-filament-tables::cell>{{ $ranking->student->class->name }}</x-filament-tables::cell>
                            <x-filament-tables::cell>{{ $ranking->total_present }}</x-filament-tables::cell>
                            <x-filament-tables::cell>{{ $ranking->score }}</x-filament-tables::cell>
                        </x-filament-tables::row>
                    @endforeach
                    <tr>
                        <x-filament-tables::cell colspan="5" class="text-center text-gray-500">Data peringkat siswa paling rajin akan muncul di sini.</x-filament-tables::cell>
                    </tr>
                </tbody>
            </x-filament-tables::table>
        </x-filament-tables::container>
    </x-filament::card>

    <x-filament::card class="mt-6">
        <h2 class="text-lg font-semibold mb-4">Top Siswa Paling Sering Terlambat</h2>
        <x-filament-tables::container>
            <x-filament-tables::table>
                <thead>
                    <tr>
                        <x-filament-tables::header-cell>Rank</x-filament-tables::header-cell>
                        <x-filament-tables::header-cell>Nama Siswa</x-filament-tables::header-cell>
                        <x-filament-tables::header-cell>Kelas</x-filament-tables::header-cell>
                        <x-filament-tables::header-cell>Total Terlambat</x-filament-tables::header-cell>
                    </tr>
                </thead>
                <tbody>
                    @foreach($this->getTopStudentsByLate() as $index => $ranking)
                        <x-filament-tables::row>
                            <x-filament-tables::cell>{{ $index + 1 }}</x-filament-tables::cell>
                            <x-filament-tables::cell>{{ $ranking->student->name }}</x-filament-tables::cell>
                            <x-filament-tables::cell>{{ $ranking->student->class->name }}</x-filament-tables::cell>
                            <x-filament-tables::cell>{{ $ranking->total_late }}</x-filament-tables::cell>
                        </x-filament-tables::row>
                    @endforeach
                    <tr>
                        <x-filament-tables::cell colspan="4" class="text-center text-gray-500">Data peringkat siswa paling sering terlambat akan muncul di sini.</x-filament-tables::cell>
                    </tr>
                </tbody>
            </x-filament-tables::table>
        </x-filament-tables::container>
    </x-filament::card>
</x-filament-panels::page>