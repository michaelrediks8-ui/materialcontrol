<x-filament-panels::page>
    <div class="flex flex-col gap-y-6">

        <!-- 1. FILTER SECTION -->
        <x-filament::section icon="heroicon-o-funnel">
            <x-slot name="heading">
                Filter Data Laporan
            </x-slot>
            {{ $this->form }}
        </x-filament::section>

        <!-- 2. STATS SUMMARY -->
        @php
            $summary = $this->getSummary();
        @endphp
        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
            <x-filament::section>
                <div class="flex items-center gap-x-4">
                    <div class="p-3 rounded-lg bg-primary-500/10">
                        <x-heroicon-o-briefcase class="w-6 h-6 text-primary-600 dark:text-primary-400" />
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Project</p>
                        <p class="text-2xl font-bold text-gray-950 dark:text-white">{{ $summary['total_project'] }}</p>
                    </div>
                </div>
            </x-filament::section>

            <x-filament::section>
                <div class="flex items-center gap-x-4">
                    <div class="p-3 rounded-lg bg-warning-500/10">
                        <x-heroicon-o-cube class="w-6 h-6 text-warning-600 dark:text-warning-400" />
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Varian Material</p>
                        <p class="text-2xl font-bold text-gray-950 dark:text-white">{{ $summary['total_barang'] }}</p>
                    </div>
                </div>
            </x-filament::section>

            <x-filament::section>
                <div class="flex items-center gap-x-4">
                    <div class="p-3 rounded-lg bg-success-500/10">
                        <x-heroicon-o-document-text class="w-6 h-6 text-success-600 dark:text-success-400" />
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Baris Data</p>
                        <p class="text-2xl font-bold text-gray-950 dark:text-white">{{ $summary['total_item'] }}</p>
                    </div>
                </div>
            </x-filament::section>
        </div>

        <!-- 3. DATA TABEL PER PROJECT -->
        @php
            $groupedRows = $this->getGroupedRows();
        @endphp

        <div class="flex flex-col gap-y-6">
            @forelse($groupedRows as $projectName => $items)
                <x-filament::section>
                    <!-- Header Section Project -->
                    <x-slot name="heading">
                        <div class="flex items-center gap-x-2">
                            <x-heroicon-m-folder-open class="w-5 h-5 text-gray-400" />
                            <span class="text-base">{{ $projectName }}</span>
                        </div>
                    </x-slot>
                    
                    <x-slot name="headerEnd">
                        <x-filament::badge color="info">
                            {{ count($items) }} Material Dipakai
                        </x-filament::badge>
                    </x-slot>

                    <!-- Tabel Flush (Menempel rapi dengan ujung section khas Filament) -->
                    <div class="-mx-6 -mb-6 border-t border-gray-200 dark:border-white/10 overflow-x-auto">
                        <table class="w-full text-left divide-y divide-gray-200 dark:divide-white/5">
                            <thead class="bg-gray-50 dark:bg-white/5">
                                <tr>
                                    <th class="px-6 py-3 text-sm font-semibold text-gray-950 dark:text-white whitespace-nowrap">Kode Material</th>
                                    <th class="px-6 py-3 text-sm font-semibold text-gray-950 dark:text-white whitespace-nowrap">Nama Material</th>
                                    <th class="px-6 py-3 text-sm font-semibold text-gray-950 dark:text-white whitespace-nowrap">Pengambilan Terakhir</th>
                                    <th class="px-6 py-3 text-sm font-semibold text-gray-950 dark:text-white whitespace-nowrap text-right">Total Dipakai</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-white/5 whitespace-nowrap">
                                @foreach($items as $item)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-white/5 transition duration-75">
                                        
                                        <!-- Kolom Kode -->
                                        <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400 font-mono">
                                            {{ $item['kode_barang'] }}
                                        </td>
                                        
                                        <!-- Kolom Nama -->
                                        <td class="px-6 py-4 text-sm font-medium text-gray-950 dark:text-white">
                                            {{ $item['nama_barang'] }}
                                        </td>
                                        
                                        <!-- Kolom Tanggal & Request -->
                                        <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                            <div class="flex flex-col gap-y-1">
                                                <span class="font-medium text-gray-950 dark:text-white">
                                                    {{ $item['tanggal_terakhir'] ? \Carbon\Carbon::parse($item['tanggal_terakhir'])->translatedFormat('d M Y') : '-' }}
                                                </span>
                                                <span class="flex items-center gap-x-1 text-xs">
                                                    <x-heroicon-m-user class="w-3.5 h-3.5" />
                                                    Req: {{ $item['request_oleh'] }}
                                                </span>
                                            </div>
                                        </td>
                                        
                                        <!-- Kolom Total (Badge) -->
                                        <td class="px-6 py-4 text-sm text-right">
                                            <x-filament::badge color="success" size="sm">
                                                {{ $this->formatQty($item['total_qty']) }} {{ $item['satuan'] }}
                                            </x-filament::badge>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </x-filament::section>
            @empty
                <!-- Tampilan Jika Kosong -->
                <x-filament::section>
                    <div class="flex flex-col items-center justify-center p-6 mx-auto text-center">
                        <div class="p-4 mb-4 bg-gray-100 rounded-full dark:bg-gray-800">
                            <x-heroicon-o-magnifying-glass class="w-6 h-6 text-gray-500 dark:text-gray-400" />
                        </div>
                        <h3 class="text-lg font-medium text-gray-950 dark:text-white">Belum ada pemakaian</h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Coba ubah rentang waktu atau project di filter atas.</p>
                    </div>
                </x-filament::section>
            @endforelse
        </div>

    </div>
</x-filament-panels::page>