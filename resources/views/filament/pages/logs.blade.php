<x-filament::page>
    <div class="space-y-4" @if($live) wire:poll.5s="refreshEntries" @endif>
        <div class="grid grid-cols-1 lg:grid-cols-4 gap-3">
            <div class="lg:col-span-2">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">File Log</label>
                <select wire:model="selectedFile" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 shadow-sm focus:border-primary-500 focus:ring-primary-500 text-sm">
                    @forelse ($files as $f)
                        <option value="{{ $f['path'] }}">{{ $f['basename'] }} — {{ $this->humanSize($f['size']) }} — {{ date('Y-m-d H:i:s', $f['mtime']) }}</option>
                    @empty
                        <option value="">(Tidak ada file log)</option>
                    @endforelse
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Filter Level</label>
                <div class="mt-1 flex flex-wrap gap-2 text-sm">
                    @php $allLevels = ['error' => 'Error', 'warning' => 'Warning', 'info' => 'Info', 'debug' => 'Debug']; @endphp
                    @foreach ($allLevels as $val => $label)
                        <label class="inline-flex items-center gap-1">
                            <input type="checkbox" wire:model="levels" value="{{ $val }}" class="rounded border-gray-300 dark:border-gray-700 text-primary-600 focus:ring-primary-500">
                            <span>{{ $label }}</span>
                        </label>
                    @endforeach
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Kata Kunci</label>
                <input type="text" wire:model.debounce.500ms="keyword" placeholder="Cari pesan/context..." class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 shadow-sm focus:border-primary-500 focus:ring-primary-500 text-sm">
            </div>
        </div>

        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <label class="text-sm text-gray-700 dark:text-gray-300">Tampilkan</label>
                <select wire:model="limit" class="rounded-md border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 shadow-sm focus:border-primary-500 focus:ring-primary-500 text-sm">
                    <option value="100">100</option>
                    <option value="200">200</option>
                    <option value="500">500</option>
                </select>
                <span class="text-sm text-gray-600 dark:text-gray-400">entri terbaru</span>
            </div>
            <div class="flex items-center gap-2">
                <label class="inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                    <input type="checkbox" wire:model="live" class="rounded border-gray-300 dark:border-gray-700 text-primary-600 focus:ring-primary-500">
                    <span>Live tail (5s)</span>
                </label>
                <x-filament::button wire:click="refreshEntries" color="gray">Refresh</x-filament::button>
            </div>
        </div>

        @if (empty($entries))
            <div class="rounded-md border border-gray-200 bg-gray-50 p-4 text-gray-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100">
                Tidak ada entri untuk filter saat ini.
            </div>
        @else
            <div class="space-y-2">
                @foreach ($entries as $idx => $e)
                    @php
                        $level = strtolower($e['level'] ?? 'info');
                        $levelColors = [
                            'error' => 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-200',
                            'warning' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/40 dark:text-yellow-200',
                            'info' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-200',
                            'debug' => 'bg-gray-200 text-gray-800 dark:bg-gray-700 dark:text-gray-200',
                        ];
                        $badge = $levelColors[$level] ?? $levelColors['info'];
                    @endphp
                    <details class="group rounded-md border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
                        <summary class="list-none cursor-pointer select-none px-3 py-2 flex items-center gap-3">
                            <span class="text-xs font-mono text-gray-500 dark:text-gray-400">{{ $e['ts'] ?? '-' }}</span>
                            <span class="inline-flex items-center rounded px-1.5 py-0.5 text-[11px] {{ $badge }} capitalize">{{ $level }}</span>
                            <span class="text-sm text-gray-800 dark:text-gray-100 truncate">{{ $e['message'] ?? '' }}</span>
                            <span class="ml-auto text-xs text-gray-500 dark:text-gray-400">Detail</span>
                        </summary>
                        <div class="px-3 pb-3 space-y-2">
                            <div class="rounded-md border border-gray-200 bg-gray-50 p-2 text-gray-800 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100 overflow-x-auto">
                                <div class="text-[11px] font-medium text-gray-600 dark:text-gray-300 mb-1">Message</div>
                                <pre class="font-mono text-xs whitespace-pre-wrap">{{ $e['message'] ?? '' }}</pre>
                            </div>
                            @if (!empty($e['context']))
                                <div class="rounded-md border border-gray-200 bg-gray-50 p-2 text-gray-800 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100 overflow-x-auto">
                                    <div class="text-[11px] font-medium text-gray-600 dark:text-gray-300 mb-1">Context</div>
                                    <pre class="font-mono text-xs whitespace-pre">{{ json_encode($e['context'], JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE) }}</pre>
                                </div>
                            @endif
                            @if (!empty($e['stack']))
                                <div class="rounded-md border border-gray-200 bg-gray-50 p-2 text-gray-800 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100 overflow-x-auto">
                                    <div class="text-[11px] font-medium text-gray-600 dark:text-gray-300 mb-1">Stack Trace</div>
                                    <pre class="font-mono text-xs whitespace-pre">{{ implode("\n", $e['stack']) }}</pre>
                                </div>
                            @endif
                            <div class="rounded-md border border-gray-200 bg-gray-50 p-2 text-gray-800 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100 overflow-x-auto">
                                <div class="text-[11px] font-medium text-gray-600 dark:text-gray-300 mb-1">Raw</div>
                                <pre class="font-mono text-xs whitespace-pre">{{ $e['raw'] ?? '' }}</pre>
                            </div>
                        </div>
                    </details>
                @endforeach
            </div>
        @endif
    </div>
</x-filament::page>
