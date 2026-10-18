<x-filament-panels::page>
    <div class="space-y-6">
        {{-- 1. FILTER FORM --}}
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-white/10 rounded-xl shadow-sm p-6">
            <form wire:submit.prevent>
                {{ $this->form }}
            </form>

            <div class="mt-4 pt-4 border-t border-gray-100 dark:border-white/5 flex justify-end">
                <x-filament::button
                    wire:click="export"
                    icon="heroicon-m-arrow-down-tray"
                    color="gray"
                    labeled-from="sm"
                >
                    Export Excel
                </x-filament::button>
            </div>
        </div>

        @php
            $summary = $this->getSummary();
            $rows = $this->getRows();
        @endphp

        {{-- 2. SUMMARY CARDS --}}
        {{-- Ubah gap-4 menjadi gap-6 untuk jarak antar kartu yang lebih lega --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6"> 
            
            {{-- Tambahkan p-6 (padding 24px) agar ruang dalam kartu lebih luas --}}
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-white/10 rounded-xl p-6 flex items-center justify-between shadow-sm">
                <div>
                    <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Qty Masuk</div>
                    {{-- Tambahkan mt-2 untuk jarak antara judul dan angka, dan text-3xl agar angka lebih tegas --}}
                    <div class="text-3xl font-bold text-gray-900 dark:text-white mt-2">{{ $this->formatQty($summary['total_masuk']) }}</div>
                </div>
                {{-- Perbesar sedikit area ikon dengan p-3.5 dan rounded-xl --}}
                <div class="p-3.5 bg-gray-50 dark:bg-gray-800 rounded-xl text-gray-500 dark:text-gray-400 ring-1 ring-inset ring-gray-200 dark:ring-white/10">
                    <x-heroicon-o-arrow-down-tray class="w-6 h-6" />
                </div>
            </div>

            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-white/10 rounded-xl p-6 flex items-center justify-between shadow-sm">
                <div>
                    <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Qty Keluar</div>
                    <div class="text-3xl font-bold text-gray-900 dark:text-white mt-2">{{ $this->formatQty($summary['total_keluar']) }}</div>
                </div>
                <div class="p-3.5 bg-gray-50 dark:bg-gray-800 rounded-xl text-gray-500 dark:text-gray-400 ring-1 ring-inset ring-gray-200 dark:ring-white/10">
                    <x-heroicon-o-arrow-up-tray class="w-6 h-6" />
                </div>
            </div>

            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-white/10 rounded-xl p-6 flex items-center justify-between shadow-sm">
                <div>
                    <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Transaksi</div>
                    <div class="text-3xl font-bold text-gray-900 dark:text-white mt-2">{{ number_format($summary['total_transaksi'], 0, ',', '.') }}</div>
                </div>
                <div class="p-3.5 bg-gray-50 dark:bg-gray-800 rounded-xl text-gray-500 dark:text-gray-400 ring-1 ring-inset ring-gray-200 dark:ring-white/10">
                    <x-heroicon-o-document-text class="w-6 h-6" />
                </div>
            </div>
        </div>

        {{-- 3. TABEL DATA --}}
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-white/10 rounded-xl shadow-sm overflow-hidden">
            @if(count($rows) > 0)
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm whitespace-nowrap">
                        <thead class="bg-gray-50 dark:bg-gray-800 border-b border-gray-200 dark:border-white/10">
                            <tr>
                                <th scope="col" class="px-4 py-3 font-medium text-gray-500 dark:text-gray-400">Tanggal & Jam</th>
                                <th scope="col" class="px-4 py-3 font-medium text-gray-500 dark:text-gray-400">No. Transaksi</th>
                                <th scope="col" class="px-4 py-3 font-medium text-gray-500 dark:text-gray-400">Jenis</th>
                                <th scope="col" class="px-4 py-3 font-medium text-gray-500 dark:text-gray-400">Barang</th>
                                <th scope="col" class="px-4 py-3 font-medium text-gray-500 dark:text-gray-400">Asal</th>
                                <th scope="col" class="px-4 py-3 font-medium text-gray-500 dark:text-gray-400">Tujuan</th>
                                <th scope="col" class="px-4 py-3 font-medium text-gray-500 dark:text-gray-400 text-right">Qty</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                            @foreach($rows as $row)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                                    {{-- Tanggal --}}
                                    <td class="px-4 py-3 text-gray-600 dark:text-gray-300">
                                        {{ \Carbon\Carbon::parse($row['tanggal'])->format('d M Y, H:i') }}
                                    </td>

                                    {{-- No Transaksi --}}
                                    <td class="px-4 py-3 font-mono text-xs font-medium text-gray-900 dark:text-gray-200">
                                        {{ $row['no_transaksi'] }}
                                    </td>

                                    {{-- Badge Jenis --}}
                                    <td class="px-4 py-3">
                                        @switch($row['jenis_transaksi'])
                                            @case('masuk')
                                                <span class="inline-flex items-center rounded-md bg-emerald-50 px-2 py-1 text-xs font-medium text-emerald-700 ring-1 ring-inset ring-emerald-600/20 dark:bg-emerald-400/10 dark:text-emerald-400 dark:ring-emerald-400/20">Masuk</span>
                                                @break
                                            @case('keluar')
                                                <span class="inline-flex items-center rounded-md bg-rose-50 px-2 py-1 text-xs font-medium text-rose-700 ring-1 ring-inset ring-rose-600/10 dark:bg-rose-400/10 dark:text-rose-400 dark:ring-rose-400/20">Keluar</span>
                                                @break
                                            @case('transfer')
                                                <span class="inline-flex items-center rounded-md bg-blue-50 px-2 py-1 text-xs font-medium text-blue-700 ring-1 ring-inset ring-blue-700/10 dark:bg-blue-400/10 dark:text-blue-400 dark:ring-blue-400/20">Transfer</span>
                                                @break
                                            @default
                                                <span class="inline-flex items-center rounded-md bg-gray-50 px-2 py-1 text-xs font-medium text-gray-600 ring-1 ring-inset ring-gray-500/10">{{ $row['jenis_transaksi'] }}</span>
                                        @endswitch
                                    </td>

                                    {{-- Kode & Nama Barang --}}
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-2">
                                            <span class="inline-flex items-center rounded bg-gray-100 px-1.5 py-0.5 text-[10px] font-mono font-medium text-gray-600 dark:bg-gray-800 dark:text-gray-300">
                                                {{ $row['kode_barang'] }}
                                            </span>
                                            <span class="font-medium text-gray-900 dark:text-gray-100">{{ $row['nama_barang'] }}</span>
                                        </div>
                                    </td>

                                    {{-- Asal --}}
                                    <td class="px-4 py-3 text-gray-500 dark:text-gray-400">
                                        {{ $row['asal'] ?: '-' }}
                                    </td>

                                    {{-- Tujuan + Penanda Drop/Transit --}}
                                    <td class="px-4 py-3">
                                        <div class="text-gray-900 dark:text-gray-200">{{ $row['tujuan'] ?: '-' }}</div>
                                        @if(($row['jenis_transaksi'] === 'masuk') && in_array($row['tujuan_type'] ?? '', ['project', 'purchase_order']))
                                            <span class="inline-flex mt-1 items-center rounded-md bg-gray-50 px-1.5 py-0.5 text-[10px] font-medium text-gray-600 ring-1 ring-inset ring-gray-500/10 dark:bg-gray-800 dark:text-gray-400 dark:ring-white/10">Drop ke Project</span>
                                        @elseif(($row['jenis_transaksi'] === 'masuk') && ($row['tujuan_type'] ?? '') === 'gudang')
                                            <span class="inline-flex mt-1 items-center rounded-md bg-gray-50 px-1.5 py-0.5 text-[10px] font-medium text-gray-600 ring-1 ring-inset ring-gray-500/10 dark:bg-gray-800 dark:text-gray-400 dark:ring-white/10">Transit Gudang</span>
                                        @endif
                                    </td>

                                    {{-- Qty (Warna Diseragamkan) --}}
                                    <td class="px-4 py-3 text-right font-semibold text-gray-900 dark:text-white">
                                        {{ $this->formatQty($row['qty']) }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center text-gray-500 dark:text-gray-400 py-12">
                    <x-heroicon-o-inbox class="mx-auto h-8 w-8 text-gray-400 mb-3" />
                    <p>Tidak ada transaksi pada filter yang dipilih.</p>
                </div>
            @endif
        </div>
    </div>
</x-filament-panels::page>