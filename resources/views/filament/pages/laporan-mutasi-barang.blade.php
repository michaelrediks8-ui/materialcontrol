<x-filament-panels::page>
    <div>
        {{-- 1. FILTER FORM --}}
        <div class="laporan-card">
            <form wire:submit.prevent>
                {{ $this->form }}
            </form>

            <div style="display: flex; justify-content: flex-end; margin-top: 1rem; padding-top: 1rem; border-top: 1px solid #F1F5F9;">
                <x-filament::button
                    wire:click="export"
                    icon="heroicon-m-arrow-down-tray"
                    color="success"
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
        <div class="summary-grid">
            <div class="stat-box">
                <div>
                    <div class="stat-title">Total Qty Masuk</div>
                    <div class="stat-value stat-value-masuk">{{ $this->formatQty($summary['total_masuk']) }}</div>
                </div>
                <div class="stat-icon-box stat-icon-masuk">
                    <x-heroicon-o-arrow-down-tray style="width: 1.5rem; height: 1.5rem;" />
                </div>
            </div>

            <div class="stat-box">
                <div>
                    <div class="stat-title">Total Qty Keluar</div>
                    <div class="stat-value stat-value-keluar">{{ $this->formatQty($summary['total_keluar']) }}</div>
                </div>
                <div class="stat-icon-box stat-icon-keluar">
                    <x-heroicon-o-arrow-up-tray style="width: 1.5rem; height: 1.5rem;" />
                </div>
            </div>

            <div class="stat-box">
                <div>
                    <div class="stat-title">Total Transaksi</div>
                    <div class="stat-value stat-value-total">{{ number_format($summary['total_transaksi'], 0, ',', '.') }}</div>
                </div>
                <div class="stat-icon-box stat-icon-total">
                    <x-heroicon-o-document-text style="width: 1.5rem; height: 1.5rem;" />
                </div>
            </div>
        </div>

        {{-- 3. TABEL DATA --}}
        @if(count($rows) > 0)
            <div class="laporan-table-container">
                <div style="overflow-x: auto;">
                    <table class="laporan-table">
                        <thead>
                            <tr>
                                <th class="col-tanggal">Tanggal & Jam</th>
                                <th class="col-notrans">No. Transaksi</th>
                                <th class="col-jenis">Jenis</th>
                                <th class="col-barang">Barang</th>
                                <th class="col-asal">Asal</th>
                                <th class="col-tujuan">Tujuan</th>
                                <th class="col-qty" style="text-align: right;">Qty</th>
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

                                    {{-- Kode & Nama Barang --}}
                                    <td class="col-barang">
                                        <div class="barang-wrapper">
                                            <span class="kode-tag">{{ $row['kode_barang'] }}</span>
                                            <span style="font-weight: 600; color: #0F172A;">{{ $row['nama_barang'] }}</span>
                                        </div>
                                    </td>

                                    {{-- Asal --}}
                                    <td class="col-asal" style="color: #64748B;">
                                        {{ $row['asal'] ?: '-' }}
                                    </td>

                                    {{-- Tujuan + Penanda Drop/Transit --}}
                                    <td class="col-tujuan" style="color: #64748B;">
                                        <div style="font-weight: 600; color: #1E293B;">{{ $row['tujuan'] ?: '-' }}</div>
                                        @if(($row['jenis_transaksi'] === 'masuk') && in_array($row['tujuan_type'] ?? '', ['project', 'purchase_order']))
                                            <span class="badge-pill-warning" style="font-size: 0.65rem; padding: 0.15rem 0.5rem; margin-top: 0.25rem;">🚚 Drop ke Project</span>
                                        @elseif(($row['jenis_transaksi'] === 'masuk') && ($row['tujuan_type'] ?? '') === 'gudang')
                                            <span style="font-size: 0.65rem; color: #64748B; font-weight: 600; display: inline-block; margin-top: 0.25rem;">📦 Transit Gudang</span>
                                        @endif
                                    </td>

                                    {{-- Qty --}}
                                    <td class="col-qty" style="text-align: right; font-weight: 800; font-size: 1rem;
                                        @if($row['jenis_transaksi'] === 'masuk') color: #059669;
                                        @elseif($row['jenis_transaksi'] === 'keluar') color: #E11D48;
                                        @else color: #2563EB; @endif">
                                        {{ $this->formatQty($row['qty']) }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @else
            <div class="laporan-card" style="text-align: center; color: #94A3B8; padding: 3rem 1rem;">
                Tidak ada transaksi pada filter yang dipilih.
            </div>
        @endif
    </div>
</x-filament-panels::page>