<x-filament-panels::page>

    <x-filament-panels::form wire:submit="save">
        {{ $this->infolist }}
    </x-filament-panels::form>

    <x-filament::section collapsible>
        <x-slot name="heading">
            Rekapitulasi Absensi Bulanan
        </x-slot>

        <div class="flex items-center gap-4 mb-4">
            <x-filament::input.wrapper class="w-full md:w-1/2">
                <x-filament::input.select wire:model.live="selectedMonth">
                    @foreach (range(1, 12) as $month)
                        <option value="{{ $month }}">{{ \Carbon\Carbon::create()->month($month)->format('F') }}</option>
                    @endforeach
                </x-filament::input.select>
            </x-filament::input.wrapper>
            <x-filament::input.wrapper class="w-full md:w-1/2">
                <x-filament::input.select wire:model.live="selectedYear">
                    @php
                        $currentYear = now()->year;
                    @endphp
                    @foreach (range($currentYear - 5, $currentYear) as $year)
                        <option value="{{ $year }}">{{ $year }}</option>
                    @endforeach
                </x-filament::input.select>
            </x-filament::input.wrapper>
        </div>

        @php
            $recap = $this->getMonthlyRecapSummary();
            $total_hari_absensi = $recap->total_hari_absensi ?? 0;
            $total_hadir = $recap->total_hadir ?? 0;
            $total_terlambat = $recap->total_terlambat ?? 0;
            $total_tidak_hadir = $recap->total_tidak_hadir ?? 0;
            $total_sakit = $recap->total_sakit ?? 0;
            $total_izin = $recap->total_izin ?? 0;

            $percentage = 0;
            if ($total_hari_absensi > 0) {
                $total_hadir_tercatat = $total_hadir + $total_terlambat + $total_sakit + $total_izin;
                $percentage = round(($total_hadir_tercatat / $total_hari_absensi) * 100, 2);
            }

            $percentageColor = 'gray';
            if ($percentage >= 90) $percentageColor = 'success';
            elseif ($percentage >= 75) $percentageColor = 'warning';
            else $percentageColor = 'danger';
        @endphp

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <x-filament::card>
                <p class="text-sm text-gray-500">Total Hadir</p>
                <p class="text-2xl font-bold">{{ $total_hadir }}</p>
            </x-filament::card>
            <x-filament::card>
                <p class="text-sm text-gray-500">Total Terlambat</p>
                <p class="text-2xl font-bold">{{ $total_terlambat }}</p>
            </x-filament::card>
            <x-filament::card>
                <p class="text-sm text-gray-500">Total Alpa</p>
                <p class="text-2xl font-bold">{{ $total_tidak_hadir }}</p>
            </x-filament::card>
            <x-filament::card>
                <p class="text-sm text-gray-500">Total Sakit</p>
                <p class="text-2xl font-bold">{{ $total_sakit }}</p>
            </x-filament::card>
            <x-filament::card>
                <p class="text-sm text-gray-500">Total Izin</p>
                <p class="text-2xl font-bold">{{ $total_izin }}</p>
            </x-filament::card>
            <x-filament::card>
                <p class="text-sm text-gray-500">Kehadiran Total</p>
                <p class="text-2xl font-bold">
                    <x-filament::badge :color="$percentageColor">
                        {{ $percentage }}%
                    </x-filament::badge>
                </p>
            </x-filament::card>
        </div>
    </x-filament::section>

    {{-- Container untuk menampung dua chart dalam layout grid 2 kolom --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">

        {{-- Kolom 1: Statistik Kehadiran Bulanan --}}
        <x-filament::section collapsible>
            <x-slot name="heading">
                Statistik Kehadiran Bulanan
            </x-slot>

            @livewire(App\Filament\Resources\StudentResource\Widgets\StudentAttendanceChart::class, [
                'record' => $this->record,
                'selectedMonth' => $this->selectedMonth,
                'selectedYear' => $this->selectedYear
            ])
        </x-filament::section>

        {{-- Kolom 2: Statistik Kehadiran Tahunan --}}
        <x-filament::section collapsible>
            <x-slot name="heading">
                Statistik Kehadiran Tahunan
            </x-slot>

            <div class="my-4">
                <x-filament::input.wrapper class="w-full md:w-1/2">
                    <x-filament::input.select wire:model.live="selectedChartYear">
                        @php
                            $currentYear = now()->year;
                        @endphp
                        @foreach (range($currentYear - 5, $currentYear) as $year)
                            <option value="{{ $year }}">{{ $year }}</option>
                        @endforeach
                    </x-filament::input.select>
                </x-filament::input.wrapper>
            </div>

            @livewire(App\Filament\Resources\StudentResource\Widgets\StudentMonthlyAttendanceBarChart::class, [
                'record' => $this->record,
                'selectedChartYear' => $this->selectedChartYear
            ])
        </x-filament::section>
    </div>

    <x-filament::section>
        <x-slot name="heading">
            Histori Absensi Harian
        </x-slot>

        {{ $this->getDailyHistoryTable() }}
    </x-filament::section>

    {{-- Render footer widgets --}}
    @foreach ($this->getFooterWidgets() as $widget)
        @livewire($widget, ['record' => $this->record])
    @endforeach

</x-filament-panels::page>
