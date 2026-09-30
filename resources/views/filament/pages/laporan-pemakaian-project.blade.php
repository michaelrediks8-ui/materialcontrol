<x-filament-panels::page>
    
    {{-- Form Filter di atas --}}
    <x-filament-panels::form wire:submit="submit">
        {{ $this->form }}
    </x-filament-panels::form>

    {{-- Kartu Ringkasan (Summary) --}}
    @php
        $summary = $this->getSummary();
        $groupedRows = collect($this->getRows())->groupBy('project');
    @endphp
    
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
        <x-filament::section>
            <div class="flex items-center gap-4">
                <div class="p-3 bg-primary-500/10 text-primary-600 rounded-xl">
                    <x-heroicon-o-briefcase class="w-6 h-6" />
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400 font-medium">Total Project Terlibat</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $summary['total_project'] }}</p>
                </div>
            </div>
        </x-filament::section>

        <x-filament::section>
            <div class="flex items-center gap-4">
                <div class="p-3 bg-success-500/10 text-success-600 rounded-xl">
                    <x-heroicon-o-cube class="w-6 h-6" />
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400 font-medium">Macam / Jenis Material</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $summary['total_barang'] }}</p>
                </div>
            </div>
        </x-filament::section>
        
        <x-filament::section>
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400 font-medium">Aksi Ekspor</p>
                    <p class="text-sm text-gray-900 dark:text-gray-300 mt-1">Unduh laporan ke Excel</p>
                </div>
                <x-filament::button wire:click="export" color="success" icon="heroicon-o-arrow-down-tray">
                    Export .xlsx
                </x-filament::button>
            </div>
        </x-filament::section>
    </div>

    {{-- Looping Tabel per Project --}}
    @forelse($groupedRows as $projectName => $items)
        <x-filament::section class="mb-2" collapsible>
            {{-- Header Project --}}
            <x-slot name="heading">
                <div class="flex items-center gap-2 text-primary-600 dark:text-primary-400">
                    <x-heroicon-o-building-office-2 class="w-6 h-6" />
                    <span class="text-lg font-bold">{{ $projectName }}</span>
                    <x-filament::badge color="info" size="sm" class="ml-2">
                        {{ count($items) }} Material
                    </x-filament::badge>
                </div>
            </x-slot>

            {{-- Tabel Material di dalam Project --}}
            <div class="overflow-x-auto -mx-6 -mb-6">
                <table class="w-full text-left divide-y table-auto fi-ta-table divide-gray-200 dark:divide-white/5">
                    <thead class="bg-gray-50 dark:bg-white/5">
                        <tr>
                            <th class="px-6 py-3 text-sm font-semibold text-gray-950 dark:text-white w-32">KODE</th>
                            <th class="px-6 py-3 text-sm font-semibold text-gray-950 dark:text-white">NAMA MATERIAL</th>
                            {{-- 💡 Kolom Baru --}}
                            <th class="px-6 py-3 text-sm font-semibold text-gray-950 dark:text-white">PENGAMBILAN TERAKHIR</th>
                            <th class="px-6 py-3 text-sm font-semibold text-gray-950 dark:text-white text-right">TOTAL DIPAKAI</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-white/5">
                        @foreach($items as $row)
                            <tr class="hover:bg-gray-50 dark:hover:bg-white/5 transition duration-75">
                                <td class="px-6 py-3 text-sm text-gray-700 dark:text-gray-300">
                                    <x-filament::badge color="gray">{{ $row['kode_barang'] }}</x-filament::badge>
                                </td>
                                <td class="px-6 py-3 text-sm font-medium text-gray-900 dark:text-gray-200">
                                    {{ $row['nama_barang'] }}
                                </td>
                                {{-- 💡 Data Kolom Baru (Tanggal + Requester) --}}
                                <td class="px-6 py-3 text-sm text-gray-700 dark:text-gray-300">
                                    <div class="flex flex-col">
                                        <span class="font-medium text-gray-900 dark:text-gray-100">
                                            {{ \Carbon\Carbon::parse($row['tanggal_terakhir'])->format('d M Y') }}
                                        </span>
                                        <span class="text-xs text-gray-500 dark:text-gray-400">
                                            Req: {{ $row['request_oleh'] !== '-' ? $row['request_oleh'] : 'Tidak diketahui' }}
                                        </span>
                                    </div>
                                </td>
                                <td class="px-6 py-3 text-sm font-bold text-gray-900 dark:text-white text-right">
                                    {{ $this->formatQty($row['total_qty']) }} 
                                    <span class="text-xs font-normal text-gray-500 dark:text-gray-400 ml-1">{{ $row['satuan'] }}</span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-filament::section>
    @empty
        <x-filament::section>
            <div class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                <x-heroicon-o-document-magnifying-glass class="w-12 h-12 mx-auto text-gray-400 mb-4" />
                <p class="font-medium text-lg">Tidak ada data pemakaian</p>
                <p class="text-sm">Silakan sesuaikan filter tanggal atau project di atas.</p>
            </div>
        </x-filament::section>
    @endforelse

</x-filament-panels::page>