<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Surat Jalan - {{ $mutasi->no_surat_jalan }}</title>
    <style>
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 11px;
            color: #222;
            line-height: 1.4;
        }
        /* KOP SURAT TABLE */
        .kop-table {
            width: 100%;
            border-collapse: collapse;
            border-bottom: 3px solid #000;
            padding-bottom: 8px;
            margin-bottom: 15px;
        }
        .kop-table td {
            vertical-align: middle;
        }
        .kop-logo {
            width: 90px;
            text-align: left;
        }
        .kop-text {
            text-align: center;
        }
        .kop-text h2 {
            margin: 0;
            font-size: 18px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .kop-text p {
            margin: 2px 0;
            font-size: 10px;
        }
        .kop-text .email {
            font-style: italic;
            color: #1a0dab;
        }

        /* JUDUL DOKUMEN */
        .doc-title {
            text-align: center;
            margin-bottom: 15px;
        }
        .doc-title h3 {
            margin: 0;
            font-size: 14px;
            text-decoration: underline;
            text-transform: uppercase;
        }
        .doc-title p {
            margin: 3px 0 0 0;
            font-size: 12px;
            font-weight: bold;
        }

        /* INFORMASI TRANSAKSI */
        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        .info-table td {
            padding: 3px 4px;
            vertical-align: top;
            font-size: 11px;
        }

        /* TABEL DETAIL ALAT */
        .detail-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 25px;
        }
        .detail-table th, .detail-table td {
            border: 1px solid #333;
            padding: 6px 8px;
            font-size: 10px;
        }
        .detail-table th {
            background-color: #f0f0f0;
            text-transform: uppercase;
            font-weight: bold;
            text-align: center;
        }

        /* TANDA TANGAN */
        .ttd-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        .ttd-table td {
            text-align: center;
            vertical-align: top;
            width: 33.33%;
            font-size: 10px;
        }
        .space-ttd {
            height: 50px;
        }
        /* Nama penandatangan: tinggi tetap supaya ketiganya sejajar (tanpa garis) */
        .garis-ttd {
            width: 75%;
            height: 16px;
            margin: 0 auto;
            font-weight: bold;
            text-align: center;
        }
        .jabatan-ttd {
            margin-top: 3px;
            font-size: 10px;
        }
    </style>
</head>
<body>

@php
    // Membaca langsung logo dari public/images/logo-stp.png
    $logoPath = public_path('images/logo-stp.png');
    $logoBase64 = null;

    if (file_exists($logoPath)) {
        $type = pathinfo($logoPath, PATHINFO_EXTENSION);
        $data = file_get_contents($logoPath);
        $logoBase64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
    }

    // Helper untuk mengambil jabatan berdasarkan nama
    if (! function_exists('getJabatanKaryawan')) {
    function getJabatanKaryawan($nama, $defaultRole = '') {
        if (empty($nama)) {
            return !empty($defaultRole) ? "({$defaultRole})" : '';
        }
        
        $jabatan = \App\Models\Karyawan::jabatanDari($nama);
        if (!empty($jabatan)) {
            return "({$jabatan})";
        }
        
        return !empty($defaultRole) ? "({$defaultRole})" : '';
    }
    }
@endphp

    <!-- KOP SURAT DENGAN LOGO PERUSAHAAN -->
    <table class="kop-table">
    <tr>
        <td class="kop-logo">
            @if($logoBase64)
                <img src="{{ $logoBase64 }}" style="height: 65px; width: auto; max-width: 85px;" alt="Logo STP">
            @else
                <div style="width: 52px; height: 52px; border-radius: 50%; border: 2.5px solid #1e3a8a; text-align: center; line-height: 50px; font-weight: bold; font-size: 15px; color: #1e3a8a; font-family: sans-serif;">
                    STP
                </div>
            @endif
        </td>
        <td class="kop-text">
            <h2>SARANA TEKNIK PERKASA</h2>
            <p>Kantor Pusat : Jln. Pondok Cabe 3 Ilir</p>
            <p>Pamulang, Tangerang Selatan, Banten 15418</p>
            <p>Telp : 0622-7296905 / 081317060060</p>
            <p class="email">Email : sarana.stp@gmail.com</p>
        </td>
        <td style="width: 85px;"><!-- Penyeimbang Kanan --></td>
    </tr>
</table>

    <!-- JUDUL DOKUMEN -->
    <div class="doc-title">
        <h3>Bukti Perpindahan Alat</h3>
        <p>NO: {{ $mutasi->no_surat_jalan ?? '-' }}</p>
    </div>

    <!-- INFORMASI DETAIL PERPINDAHAN -->
    <table class="info-table">
        <tr>
            <td width="16%"><strong>Tanggal & Jam</strong></td>
            <td width="34%">: {{ \Carbon\Carbon::parse($mutasi->tanggal_mutasi)->translatedFormat('d F Y H:i') }} WIB</td>
            <td width="16%"><strong>Dari Gudang</strong></td>
            <td width="34%">: {{ $mutasi->jenis_mutasi === 'transfer' ? ($mutasi->namaAsal() ?: '-') : '-' }}</td>
        </tr>
        <tr>
            <td><strong>Jenis Mutasi</strong></td>
            <td>: {{ match($mutasi->jenis_mutasi) {
                'registrasi' => 'Registrasi / Stok Awal',
                'transfer' => 'Transfer Antar Gudang',
                'rusak' => 'Tandai Rusak',
                'perbaikan' => 'Selesai Perbaikan',
                default => strtoupper($mutasi->jenis_mutasi ?? '-')
            } }}</td>
            <td><strong>Di / Ke Gudang</strong></td>
            <td>: {{ $mutasi->namaTujuan() ?: '-' }}</td>
        </tr>
        <tr>
            <td><strong>Penanggung Jawab</strong></td>
            <td>: {{ $mutasi->disetujui_oleh ?? '-' }}</td>
            <td><strong>Catatan</strong></td>
            <td>: {{ $mutasi->catatan ?? '-' }}</td>
        </tr>
        <tr>
            
        </tr>
    </table>

    <!-- TABEL DETAIL ALAT -->
    <table class="detail-table">
        <thead>
            <tr>
                <th width="5%">NO</th>
                <th width="20%">KODE ALAT</th>
                <th width="35%">NAMA ALAT</th>
                <th width="12%">KONDISI BAIK</th>
                <th width="12%">KONDISI RUSAK</th>
                <th width="16%">TOTAL QTY</th>
            </tr>
        </thead>
        <tbody>
            @forelse($mutasi->details as $index => $detail)
                @php
                    $baik = (int) ($detail->jumlah_baik ?? 0);
                    $rusak = (int) ($detail->jumlah_rusak ?? 0);
                    $total = $detail->jumlah > 0 ? (int) $detail->jumlah : ($baik + $rusak);
                    
                    $satuan = 'Unit';
                    if (!empty($detail->alat?->satuan)) {
                        $satuan = is_object($detail->alat->satuan) ? ($detail->alat->satuan->nama_satuan ?? 'Unit') : $detail->alat->satuan;
                    }
                @endphp
            <tr>
                <td style="text-align: center;">{{ $index + 1 }}</td>
                <td>{{ $detail->alat->kode_alat ?? '-' }}</td>
                <td>{{ $detail->alat->nama_alat ?? '-' }}</td>
                <td style="text-align: center;">{{ $baik > 0 ? $baik : '-' }}</td>
                <td style="text-align: center;">{{ $rusak > 0 ? $rusak : '-' }}</td>
                <td style="text-align: center; font-weight: bold;">{{ $total }} {{ $satuan }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="6" style="text-align: center;">Tidak ada detail alat.</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <!-- KOLOM TANDA TANGAN -->
    <table class="ttd-table">
        <tr>
            <td>
                Yang Menyerahkan,
                <div class="space-ttd"></div>
                <div class="garis-ttd">{{ $mutasi->diserahkan_oleh }}</div>
                <div class="jabatan-ttd">{{ getJabatanKaryawan($mutasi->diserahkan_oleh, 'Gudang Asal') }}</div>
            </td>
            <td>
                Yang Menerima,
                <div class="space-ttd"></div>
                <div class="garis-ttd">{{ $mutasi->diterima_oleh }}</div>
                <div class="jabatan-ttd">{{ getJabatanKaryawan($mutasi->diterima_oleh, 'Gudang Tujuan') }}</div>
            </td>
            <td>
                Mengetahui,
                <div class="space-ttd"></div>
                <div class="garis-ttd">{{ $mutasi->disetujui_oleh }}</div>
                <div class="jabatan-ttd">{{ getJabatanKaryawan($mutasi->disetujui_oleh, 'Supervisor Material Control') }}</div>
            </td>
        </tr>
    </table>

</body>
</html>