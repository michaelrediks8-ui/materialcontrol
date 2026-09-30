<x-filament-panels::page>
    <div>
        {{-- 1. FILTER FORM CARD --}}
        <div class="laporan-card">
            <form wire:submit.prevent>
                {{ $this->form }}
            </form>
        </div>

        @php
            $rows = $this->getRows();
            $satuan = $this->getSatuanKecil();
            $barangDipilih = $this->data['barang_id'] ?? null;
            $gudangDipilih = $this->data['gudang_id'] ?? null;
        @endphp

        {{-- 2. TABEL DATA RIWAYAT --}}
        @if(count($rows) > 0)
            <div class="laporan-table-container">
                <div style="overflow-x: auto;">
                    <table class="laporan-table">
                        <thead>
                            <tr>
                                <th class="col-tanggal">Tanggal & Jam</th>
                                <th class="col-notrans">No. Transaksi</th>
                                <th class="col-jenis">Jenis</th>
                                <th class="col-keterangan">Keterangan</th>
                                <th class="col-qty" style="text-align: right;">Masuk</th>
                                <th class="col-qty" style="text-align: right;">Keluar</th>
                                <th class="col-saldo" style="text-align: right;">Saldo</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($rows as $row)
                                <tr>
                                    {{-- Tanggal --}}
                                    <td class="col-tanggal" style="font-weight: 500; color: #475569;">
                                        {{ \Carbon\Carbon::parse($row['tanggal'])->format('d M Y, H:i') }}
                                    </td>

                                    {{-- No Transaksi --}}
                                    <td class="col-notrans" style="font-weight: 700; font-family: monospace; color: #1E293B;">
                                        {{ $row['no_transaksi'] }}
                                    </td>

                                    {{-- Badge Jenis --}}
                                    <td class="col-jenis">
                                        @switch($row['jenis_transaksi'])
                                            @case('masuk')
                                                <span class="badge-pill-masuk">📥 Masuk</span>
                                                @break
                                            @case('keluar')
                                                <span class="badge-pill-keluar">📤 Keluar</span>
                                                @break
                                            @case('transfer')
                                                <span class="badge-pill-transfer">🔄 Transfer</span>
                                                @break
                                            @default
                                                <span>{{ $row['jenis_transaksi'] }}</span>
                                        @endswitch
                                    </td>

                                    {{-- Keterangan --}}
                                    <td class="col-keterangan" style="color: #475569;">
                                        {{ $row['keterangan'] ?: '-' }}
                                    </td>

                                    {{-- Qty Masuk --}}
                                    <td class="col-qty" style="text-align: right; font-weight: 700; color: #059669;">
                                        {{ $row['qty_masuk'] > 0 ? $this->formatQty($row['qty_masuk']) : '-' }}
                                    </td>

                                    {{-- Qty Keluar --}}
                                    <td class="col-qty" style="text-align: right; font-weight: 700; color: #E11D48;">
                                        {{ $row['qty_keluar'] > 0 ? $this->formatQty($row['qty_keluar']) : '-' }}
                                    </td>

                                    {{-- Saldo Berjalan --}}
                                    <td class="col-saldo" style="text-align: right; font-weight: 800; font-size: 0.95rem; color: #0F172A;">
                                        {{ $this->formatQty($row['saldo']) }} 
                                        <span style="font-size: 0.75rem; font-weight: 600; color: #64748B;">{{ $satuan }}</span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @elseif($barangDipilih && $gudangDipilih)
            {{-- EMPTY STATE: BARANG DIPILIH TAPI TIDAK ADA TRANSAKSI --}}
            <div class="laporan-card" style="text-align: center; padding: 3rem 1rem;">
                <div style="font-weight: 700; font-size: 1rem; color: #475569; margin-bottom: 0.25rem;">
                    Data Riwayat Tidak Ditemukan
                </div>
                <div style="color: #94A3B8; font-size: 0.875rem;">
                    Tidak ada riwayat transaksi untuk barang ini di gudang yang dipilih.
                </div>
            </div>
        @else
            {{-- EMPTY STATE: BELUM PILIH BARANG/GUDANG --}}
            <div class="laporan-card" style="text-align: center; padding: 3rem 1rem;">
                <div style="font-weight: 700; font-size: 1rem; color: #475569; margin-bottom: 0.25rem;">
                    Pilih Filter Terlebih Dahulu
                </div>
                <div style="color: #94A3B8; font-size: 0.875rem;">
                    Silakan pilih <strong>Barang</strong> dan <strong>Gudang</strong> di atas untuk menampilkan kartu stok dan riwayat perjalanan barang.
                </div>
            </div>
        @endif
    </div>
</x-filament-panels::page>