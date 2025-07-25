<div class="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100 p-4" wire:poll.1s="refreshData">
    <!-- Auto-hide JavaScript -->
    <script>
        document.addEventListener('livewire:initialized', () => {
            Livewire.on('start-auto-hide', () => {
                setTimeout(() => {
                    @this.call('hideDetails');
                }, 10000); // Hide after 10 seconds
            });
        });
    </script>

    <!-- Welcome Screen -->
    @if(!$showDetails)
        <div class="flex items-center justify-center min-h-screen">
            <div class="text-center">
                <div class="mb-8">
                    <div class="mx-auto w-32 h-32 bg-gradient-to-r from-blue-500 to-purple-600 rounded-full flex items-center justify-center mb-4">
                        <svg class="w-16 h-16 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                        </svg>
                    </div>
                </div>
                <h1 class="text-4xl font-bold text-gray-800 mb-4">Sistem Absensi Digital</h1>
                <p class="text-xl text-gray-600 mb-8">Silakan lakukan scan fingerprint untuk melihat informasi kehadiran</p>
                
                <!-- Test button - only show in local environment -->
                @if(app()->environment('local'))
                    <div class="mb-4">
                        <button wire:click="testCalendar" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                            Test Kalender
                        </button>
                    </div>
                @endif
                <div class="animate-pulse">
                    <div class="bg-white p-6 rounded-lg shadow-lg inline-block">
                        <svg class="w-12 h-12 text-blue-500 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 4V2a1 1 0 011-1h8a1 1 0 011 1v2M7 4h10M7 4l-2 14h14l-2-14M12 8v4M9 8v4M15 8v4"></path>
                        </svg>
                        <p class="text-sm text-gray-500 mt-2">Menunggu scan...</p>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- User Details and Calendar -->
    @if($showDetails && $currentUser)
        <div class="max-w-6xl mx-auto">
            <!-- Header -->
            <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
                <div class="flex justify-between items-start">
                    <div class="flex items-center space-x-6">
                        <!-- Avatar -->
                        <div class="w-24 h-24 bg-gradient-to-r from-blue-500 to-purple-600 rounded-full flex items-center justify-center">
                            @if($currentUser->photo)
                                <img src="{{ Storage::url($currentUser->photo) }}" alt="{{ $currentUser->name }}" class="w-24 h-24 rounded-full object-cover">
                            @else
                                <span class="text-2xl font-bold text-white">{{ substr($currentUser->name, 0, 2) }}</span>
                            @endif
                        </div>
                        
                        <!-- User Info -->
                        <div>
                            <h2 class="text-3xl font-bold text-gray-800 mb-2">{{ $currentUser->name }}</h2>
                            <div class="grid grid-cols-2 gap-4 text-sm">
                                @if($currentUser instanceof \App\Models\Student)
                                    <div>
                                        <span class="text-gray-500">NISN:</span>
                                        <span class="font-semibold text-gray-800">{{ $currentUser->nis }}</span>
                                    </div>
                                    <div>
                                        <span class="text-gray-500">Kelas:</span>
                                        <span class="font-semibold text-gray-800">{{ $currentUser->class->name ?? 'Tidak Ada' }}</span>
                                    </div>
                                @else
                                    <div>
                                        <span class="text-gray-500">NIP:</span>
                                        <span class="font-semibold text-gray-800">{{ $currentUser->nip ?? 'Tidak Ada' }}</span>
                                    </div>
                                    <div>
                                        <span class="text-gray-500">Jabatan:</span>
                                        <span class="font-semibold text-gray-800">{{ $currentUser->position ?? 'Guru' }}</span>
                                    </div>
                                @endif
                                <div>
                                    <span class="text-gray-500">Jenis Kelamin:</span>
                                    <span class="font-semibold text-gray-800">{{ ucfirst($currentUser->gender->value ?? $currentUser->gender) }}</span>
                                </div>
                                @if($currentUser instanceof \App\Models\Student && $currentUser->parent_whatsapp)
                                    <div>
                                        <span class="text-gray-500">WhatsApp Ortu:</span>
                                        <span class="font-semibold text-gray-800">{{ $currentUser->parent_whatsapp }}</span>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                    
                    <!-- Status Badge -->
                    <div class="text-right">
                        <div class="bg-green-100 text-green-800 px-4 py-2 rounded-full font-semibold mb-2">
                            ✓ Scan Berhasil
                        </div>
                        <p class="text-sm text-gray-500">{{ now()->format('d/m/Y H:i:s') }}</p>
                        <button wire:click="hideDetails" class="mt-2 text-red-500 hover:text-red-700 text-sm">
                            Tutup
                        </button>
                    </div>
                </div>
            </div>

            <!-- Attendance Calendar -->
            <div class="bg-white rounded-lg shadow-lg p-6">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-2xl font-bold text-gray-800">Kalender Kehadiran</h3>
                    <div class="flex items-center space-x-4">
                        <button wire:click="previousMonth" class="p-2 hover:bg-gray-100 rounded-full">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                            </svg>
                        </button>
                        <h4 class="text-lg font-semibold text-gray-700 min-w-32 text-center">{{ $monthName }}</h4>
                        <button wire:click="nextMonth" class="p-2 hover:bg-gray-100 rounded-full">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Legend -->
                <div class="flex flex-wrap gap-4 mb-6 justify-center">
                    <div class="flex items-center space-x-2">
                        <div class="w-4 h-4 bg-green-500 rounded"></div>
                        <span class="text-sm text-gray-600">Hadir</span>
                    </div>
                    <div class="flex items-center space-x-2">
                        <div class="w-4 h-4 bg-yellow-500 rounded"></div>
                        <span class="text-sm text-gray-600">Terlambat</span>
                    </div>
                    <div class="flex items-center space-x-2">
                        <div class="w-4 h-4 bg-red-500 rounded"></div>
                        <span class="text-sm text-gray-600">Tidak Hadir</span>
                    </div>
                    <div class="flex items-center space-x-2">
                        <div class="w-4 h-4 bg-blue-500 rounded"></div>
                        <span class="text-sm text-gray-600">Izin/Sakit</span>
                    </div>
                    <div class="flex items-center space-x-2">
                        <div class="w-4 h-4 bg-gray-200 rounded"></div>
                        <span class="text-sm text-gray-600">Tidak Ada Data</span>
                    </div>
                </div>

                <!-- Calendar Grid -->
                <div class="grid grid-cols-7 gap-2">
                    <!-- Day Headers -->
                    @foreach(['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'] as $day)
                        <div class="text-center font-semibold text-gray-600 p-2">
                            {{ $day }}
                        </div>
                    @endforeach

                    <!-- Empty cells for first week -->
                    @php
                        $firstDayOfMonth = \Carbon\Carbon::create($currentYear, $currentMonth, 1);
                        $startPadding = $firstDayOfMonth->dayOfWeek; // 0=Sunday, 1=Monday, etc.
                    @endphp
                    
                    @for($i = 0; $i < $startPadding; $i++)
                        <div class="aspect-square"></div>
                    @endfor

                    <!-- Calendar Days -->
                    @foreach($attendanceCalendar as $day)
                        <div class="aspect-square relative group">
                            <div class="w-full h-full {{ $this->getStatusColor($day['status']) }} rounded-lg flex items-center justify-center text-white font-semibold text-sm hover:scale-105 transition-transform cursor-pointer
                                @if($day['is_today']) ring-2 ring-blue-400 ring-offset-2 @endif
                                @if($day['is_weekend'] && $day['status'] === 'no_data') opacity-30 @endif
                            ">
                                {{ $day['date'] }}
                            </div>
                            
                            <!-- Tooltip -->
                            <div class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 px-2 py-1 bg-gray-800 text-white text-xs rounded opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap z-10">
                                {{ $day['full_date'] }}: {{ $this->getStatusText($day['status']) }}
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif
</div>
