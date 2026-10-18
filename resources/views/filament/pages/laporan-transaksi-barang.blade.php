<x-filament-panels::page>
    <style>
        .lap-filters {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(190px, 1fr));
            gap: 12px;
            align-items: end;
        }
        .lap-filters label {
            display: block;
            font-size: 13px;
            font-weight: 500;
            margin-bottom: 4px;
        }
        .lap-actions { display: flex; gap: 8px; }
        .lap-scroll {
            overflow: auto;
            max-height: 66vh;
            border: 1px solid rgba(128, 128, 128, .25);
            border-radius: 8px;
        }
        .lap-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13.5px;
        }
        .lap-table th {
            position: sticky;
            top: 0;
            z-index: 5;
            background: rgb(243 244 246);
            text-align: left;
            font-size: 12px;
            letter-spacing: .03em;
            text-transform: uppercase;
            padding: 10px 12px;
            white-space: nowrap;
            border-bottom: 1px solid rgba(128, 128, 128, .3);
        }
        .dark .lap-table th { background: rgb(39 39 42); }
        .lap-table td {
            padding: 9px 12px;
            vertical-align: top;
            border-bottom: 1px solid rgba(128, 128, 128, .15);
        }
        .lap-table tbody tr:nth-child(even) { background: rgba(128, 128, 128, .05); }
        .lap-table tbody tr:hover { background: rgba(59, 130, 246, .07); }
        .lap-center { text-align: center; }
        .lap-nowrap { white-space: nowrap; }
        .lap-bold { font-weight: 600; }
        .lap-empty { padding: 40px; text-align: center; color: rgb(107 114 128); }
        .lap-meta { font-size: 13px; color: rgb(107 114 128); margin-bottom: 8px; }

        .lap-pager {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-top: 12px;
            font-size: 13px;
        }
        .lap-perpage { display: flex; align-items: center; gap: 8px; }
        .lap-perpage select {
            border: 1px solid rgba(128, 128, 128, .35);
            border-radius: 6px;
            padding: 4px 28px 4px 10px;
            background-color: transparent;
        }
        .lap-pages { display: flex; align-items: center; gap: 4px; }
        .lap-page {
            min-width: 34px;
            height: 34px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 1px solid rgba(128, 128, 128, .3);
            border-radius: 6px;
            text-decoration: none;
            color: inherit;
        }
        a.lap-page:hover { background: rgba(59, 130, 246, .1); }
        .lap-page.is-current { background: rgb(37 99 235); color: #fff; border-color: rgb(37 99 235); font-weight: 600; }
        .lap-page.is-disabled { opacity: .4; }
        .lap-gap { padding: 0 4px; color: rgb(107 114 128); }
    </style>

    @php
        $rows = $this->getRows();
        // Fallback default per page adalah 25
        $perPage = request('per_page', 25); 
        $current = $rows->currentPage();
        $last = $rows->lastPage();
        $start = max(1, $current - 2);
        $end = min($last, $current + 2);
    @endphp

    <x-filament::section>
        <!-- Tambahkan ID form agar bisa di-submit dari elemen select di bawah -->
        <form id="filterForm" method="GET" action="{{ url()->current() }}" class="lap-filters">
            <!-- Beri ID pada input hidden untuk per_page -->
            <input type="hidden" id="perPageInput" name="per_page" value="{{ $perPage }}">

            <div>
                <label>Dari Tanggal</label>
                <x-filament::input.wrapper>
                    <x-filament::input type="date" name="dari" value="{{ request('dari') }}" onchange="this.form.submit()" />
                </x-filament::input.wrapper>
            </div>

            <div>
                <label>Sampai Tanggal</label>
                <x-filament::input.wrapper>
                    <x-filament::input type="date" name="sampai" value="{{ request('sampai') }}" onchange="this.form.submit()" />
                </x-filament::input.wrapper>
            </div>

            <div>
                <label>Status Barang</label>
                <x-filament::input.wrapper>
                    <x-filament::input.select name="status" onchange="this.form.submit()">
                        <option value="">Semua</option>
                        @foreach ($this->getStatusOptions() as $value => $label)
                            <option value="{{ $value }}" @selected(request('status') === (string) $value)>{{ $label }}</option>
                        @endforeach
                    </x-filament::input.select>
                </x-filament::input.wrapper>
            </div>

            <div>
                <label>Project</label>
                <x-filament::input.wrapper>
                    <x-filament::input.select name="project" onchange="this.form.submit()">
                        <option value="">Semua</option>
                        @foreach ($this->getProjectOptions() as $value => $label)
                            <option value="{{ $value }}" @selected(request('project') === (string) $value)>{{ $label }}</option>
                        @endforeach
                    </x-filament::input.select>
                </x-filament::input.wrapper>
            </div>

            <div>
                <label>Cari (tekan Enter)</label>
                <x-filament::input.wrapper>
                    <x-filament::input type="text" name="search" value="{{ request('search') }}" placeholder="Barang, asal, project, PO..." />
                </x-filament::input.wrapper>
            </div>

            <div class="lap-actions">
                <x-filament::button type="submit">Terapkan</x-filament::button>
                <x-filament::button color="gray" tag="a" href="{{ url()->current() }}">Reset</x-filament::button>
            </div>
        </form>
    </x-filament::section>

    <x-filament::section>
        <div class="lap-meta">
            @if ($rows->total() > 0)
                Menampilkan {{ number_format($rows->firstItem(), 0, ',', '.') }}–{{ number_format($rows->lastItem(), 0, ',', '.') }}
                dari {{ number_format($rows->total(), 0, ',', '.') }} baris
            @else
                0 baris ditemukan
            @endif
        </div>

        <div class="lap-scroll">
            <table class="lap-table">
                <thead>
                    <tr>
                        <th class="lap-center">No</th>
                        <th>Tanggal</th>
                        <th style="min-width: 230px">Nama Barang</th>
                        <th class="lap-center">Qty</th>
                        <th class="lap-center">Satuan</th>
                        <th class="lap-center">Status</th>
                        <th style="min-width: 170px">Asal</th>
                        <th style="min-width: 150px">Nama Req</th>
                        <th style="min-width: 150px">Nama Terima</th>
                        <th style="min-width: 260px">Ke Project</th>
                        <th style="min-width: 160px">No PO</th>
                        <th style="min-width: 160px">Area</th>
                        <th style="min-width: 150px">No Surat Jalan</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rows as $r)
                        @php
                            $warna = str_contains((string) $r->status_barang, 'MASUK') ? 'success'
                                : (str_contains((string) $r->status_barang, 'KELUAR') ? 'danger' : 'gray');
                        @endphp
                        <tr>
                            <td class="lap-center">{{ $rows->firstItem() + $loop->index }}</td>
                            <td class="lap-nowrap">{{ $r->tanggal ? \Carbon\Carbon::parse($r->tanggal)->format('d/m/Y') : '-' }}</td>
                            <td class="lap-bold">{{ $r->nama_barang }}</td>
                            <td class="lap-center">{{ $r->qty }}</td>
                            <td class="lap-center">{{ $r->satuan }}</td>
                            <td class="lap-center">
                                <x-filament::badge :color="$warna" size="sm">{{ $r->status_barang }}</x-filament::badge>
                            </td>
                            <td>{{ $r->asal }}</td>
                            <td>{{ $r->nama_req }}</td>
                            <td>{{ $r->nama_terima }}</td>
                            <td>{{ $r->ke_project }}</td>
                            <td>{{ $r->no_po }}</td>
                            <td>{{ $r->area }}</td>
                            <td>{{ $r->no_surat_jalan ?: '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="13" class="lap-empty">Tidak ada data untuk filter ini.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="lap-pager">
            <div class="lap-perpage">
                <span>Per halaman</span>
                <!-- Update onchange agar memodifikasi hidden input filter form dan men-submit-nya -->
                <select onchange="document.getElementById('perPageInput').value = this.value; document.getElementById('filterForm').submit();">
                    @foreach ([25, 50, 100, 200, 'all'] as $opt)
                        <!-- Gunakan operator == agar nilai string '50' dari request cocok dengan integer 50 dari array -->
                        <option value="{{ $opt }}" @selected($perPage == $opt)>{{ $opt === 'all' ? 'Semua' : $opt }}</option>
                    @endforeach
                </select>
            </div>

            @if ($last > 1)
                <nav class="lap-pages">
                    @if ($rows->onFirstPage())
                        <span class="lap-page is-disabled">‹</span>
                    @else
                        <a class="lap-page" href="{{ $rows->previousPageUrl() }}">‹</a>
                    @endif

                    @if ($start > 1)
                        <a class="lap-page" href="{{ $rows->url(1) }}">1</a>
                        @if ($start > 2)
                            <span class="lap-gap">…</span>
                        @endif
                    @endif

                    @for ($i = $start; $i <= $end; $i++)
                        @if ($i === $current)
                            <span class="lap-page is-current">{{ $i }}</span>
                        @else
                            <a class="lap-page" href="{{ $rows->url($i) }}">{{ $i }}</a>
                        @endif
                    @endfor

                    @if ($end < $last)
                        @if ($end < $last - 1)
                            <span class="lap-gap">…</span>
                        @endif
                        <a class="lap-page" href="{{ $rows->url($last) }}">{{ $last }}</a>
                    @endif

                    @if ($rows->hasMorePages())
                        <a class="lap-page" href="{{ $rows->nextPageUrl() }}">›</a>
                    @else
                        <span class="lap-page is-disabled">›</span>
                    @endif
                </nav>
            @endif
        </div>
    </x-filament::section>
</x-filament-panels::page>