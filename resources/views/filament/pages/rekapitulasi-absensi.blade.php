<x-filament::page>
    {{-- Tabs --}}
    <div class="fi-body">
        <div class="flex justify-center">
            <x-filament::tabs
                wire:model.live="activeTab"
                class="mb-3"
            >
                <x-filament::tabs.item
                    icon="heroicon-o-calendar"
                    :badge="null"
                    :alpine-active="\Illuminate\Support\Js::from($activeTab === 'harian')"
                    :alpine-disabled="false"
                    :wire:click="'$set(\'activeTab\', \'harian\')'"
                    :active="$activeTab === 'harian'"
                >
                    Harian
                </x-filament::tabs.item>

                <x-filament::tabs.item
                    icon="heroicon-o-calendar-days"
                    :badge="null"
                    :alpine-active="\Illuminate\Support\Js::from($activeTab === 'mingguan')"
                    :alpine-disabled="false"
                    :wire:click="'$set(\'activeTab\', \'mingguan\')'"
                    :active="$activeTab === 'mingguan'"
                >
                    Mingguan
                </x-filament::tabs.item>

                <x-filament::tabs.item
                    icon="heroicon-o-calendar"
                    :badge="null"
                    :alpine-active="\Illuminate\Support\Js::from($activeTab === 'bulanan')"
                    :alpine-disabled="false"
                    :wire:click="'$set(\'activeTab\', \'bulanan\')'"
                    :active="$activeTab === 'bulanan'"
                >
                    Bulanan
                </x-filament::tabs.item>
            </x-filament::tabs>
        </div>
    </div>

    {{-- Minimal header: render table (header already contains Export button and we will move time filters to table header in PHP config) --}}
    <div class="mt-3">
        {{ $this->table }}
    </div>
</x-filament::page>
