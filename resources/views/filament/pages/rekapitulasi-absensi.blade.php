<x-filament-panels::page>


    <x-filament::tabs>
        <x-filament::tabs.item
            :active="$activeTab === 'harian'"
            wire:click="$set('activeTab', 'harian')"
        >
            Harian
        </x-filament::tabs.item>

        <x-filament::tabs.item
            :active="$activeTab === 'mingguan'"
            wire:click="$set('activeTab', 'mingguan')"
        >
            Mingguan
        </x-filament::tabs.item>

        <x-filament::tabs.item
            :active="$activeTab === 'bulanan'"
            wire:click="$set('activeTab', 'bulanan')"
        >
            Bulanan
        </x-filament::tabs.item>
    </x-filament::tabs>

    @if ($activeTab === 'bulanan')
        <div class="flex items-center gap-4 mt-4">
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
    @endif

    @if ($activeTab === 'mingguan')
        <div class="flex items-center gap-4 mt-4">
            <x-filament::input.wrapper class="w-full md:w-1/2">
                <x-filament::input
                    type="date"
                    wire:model.live="startDate"
                    id="startDate"
                />
            </x-filament::input.wrapper>
            <x-filament::input.wrapper class="w-full md:w-1/2">
                <x-filament::input
                    type="date"
                    wire:model.live="endDate"
                    id="endDate"
                />
            </x-filament::input.wrapper>
        </div>
    @endif

    <div class="mt-4">
        {{ $this->table }}
    </div>

</x-filament-panels::page>
