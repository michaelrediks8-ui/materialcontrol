<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Form Filter --}}
        <div class="p-6 bg-white rounded-xl shadow dark:bg-gray-800 border border-gray-100 dark:border-gray-700">
            {{ $this->form }}

            <div class="mt-4 flex flex-wrap justify-end gap-3">
                <x-filament::button wire:click="tampilkan" wire:loading.attr="disabled" wire:target="tampilkan" icon="heroicon-o-eye">
                    Tampilkan Data
                </x-filament::button>

                @if ($sudahTampil)
                    <x-filament::button wire:click="export" wire:loading.attr="disabled" wire:target="export" color="success" icon="heroicon-o-document-arrow-down">
                        Export to Excel (.xlsx)
                    </x-filament::button>
                @endif
            </div>
        </div>

        {{-- Preview Tabel (hanya setelah tombol Tampilkan ditekan) --}}
        <div class="overflow-x-auto bg-white rounded-xl shadow dark:bg-gray-800 border border-gray-100 dark:border-gray-700 p-4">
            <h3 class="text-lg font-bold mb-4 text-gray-800 dark:text-gray-100">Preview Data Laporan</h3>

            @if (! $sudahTampil)
                <div class="text-center py-10 text-gray-400">
                    Pilih vendor dan tanggal, lalu klik <strong>Tampilkan Data</strong>.<br>
                    Setelah data tampil, tombol Export akan muncul.
                </div>
            @else
                @php
                    $hasil = $this->getPreviewRows();
                    $rows = $hasil['rows'];
                @endphp

                <p class="mb-3 text-xs text-gray-500 dark:text-gray-400">
                    {{ number_format($hasil['total'], 0, ',', '.') }} baris ditemukan
                    @if ($hasil['total'] > count($rows))
                        — preview menampilkan {{ count($rows) }} baris pertama, file Excel tetap berisi semuanya.
                    @endif
                </p>

                <table class="w-full text-sm text-left border-collapse">
                    <thead>
                        <tr style="background-color: #16a34a; color: #ffffff; font-weight: bold;">
                            <th class="px-3 py-2 border" style="border-color: #15803d;">No</th>
                            <th class="px-3 py-2 border" style="border-color: #15803d;">No. Voucher/SJ</th>
                            <th class="px-3 py-2 border" style="border-color: #15803d;">Tanggal</th>
                            <th class="px-3 py-2 border" style="border-color: #15803d;">Description Material</th>
                            <th class="px-3 py-2 border text-right" style="border-color: #15803d;">Qty</th>
                            <th class="px-3 py-2 border" style="border-color: #15803d;">Satuan</th>
                            <th class="px-3 py-2 border" style="border-color: #15803d;">Project</th>
                            <th class="px-3 py-2 border" style="border-color: #15803d;">Request</th>
                            <th class="px-3 py-2 border text-center" style="border-color: #15803d;">Amount / Total Harga</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rows as $r)
                            <tr class="border-b hover:bg-gray-50 dark:hover:bg-gray-700">
                                <td class="px-3 py-2 border text-center">{{ $r['no'] }}</td>
                                <td class="px-3 py-2 border font-medium">{{ $r['no_voucher'] }}</td>
                                <td class="px-3 py-2 border text-center">{{ $r['tanggal'] }}</td>
                                <td class="px-3 py-2 border">{{ $r['nama_barang'] }}</td>
                                <td class="px-3 py-2 border text-right font-semibold">{{ number_format($r['qty'], 0, ',', '.') }}</td>
                                <td class="px-3 py-2 border">{{ $r['satuan'] }}</td>
                                <td class="px-3 py-2 border">{{ $r['project'] }}</td>
                                <td class="px-3 py-2 border">{{ $r['request_oleh'] }}</td>
                                <td class="px-3 py-2 border text-center {{ $r['is_last_item'] ? 'bg-yellow-300' : '' }}"></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center py-6 text-gray-400">
                                    Tidak ada data transaksi barang masuk pada periode yang dipilih.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            @endif
        </div>
    </div>
</x-filament-panels::page>