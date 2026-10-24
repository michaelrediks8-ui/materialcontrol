@php
    // Membaca langsung logo dari public/images/logo-stp.png
    $logoPath = public_path('images/logo-stp.png');
    $logoBase64 = null;

    if (file_exists($logoPath)) {
        $type = pathinfo($logoPath, PATHINFO_EXTENSION);
        $data = file_get_contents($logoPath);
        $logoBase64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
    }

    $jabatanPenerima = null;
    if (!empty($transaksi->diterima_oleh)) {
        $karyawan = \DB::table('karyawans') // Sesuaikan nama tabel karyawan Anda (misal: karyawans / karyawan)
            ->where('nama', $transaksi->diterima_oleh) // Sesuaikan nama kolom nama di tabel karyawan
            ->first();
        $jabatanPenerima = $karyawan->jabatan ?? null;
    }
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Bukti Transfer Barang - {{ $transaksi->no_transaksi }}</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 11px;
            color: #333;
            margin: 0;
            padding: 15px;
        }
        /* Styling Kop Surat */
        .kop-table {
            width: 100%;
            border-bottom: 2px solid #000;
            padding-bottom: 8px;
            margin-bottom: 20px;
            border-collapse: collapse;
        }
        .kop-table td {
            vertical-align: middle;
        }
        .kop-logo {
            width: 90px;
        }
        .kop-text {
            text-align: center;
        }
        .kop-text h2 {
            margin: 0;
            font-size: 16px;
            font-weight: bold;
            color: #111;
            letter-spacing: 0.5px;
        }
        .kop-text p {
            margin: 2px 0;
            font-size: 10px;
            color: #444;
        }
        .kop-text .email {
            font-style: italic;
        }

        /* Judul Dokumen & Info */
        .doc-title-container {
            text-align: center;
            margin-bottom: 15px;
        }
        .doc-title-container h3 {
            margin: 0 0 5px 0;
            font-size: 14px;
            text-decoration: underline;
        }
        .doc-title-container p {
            margin: 0;
            font-size: 11px;
        }

        .info-table {
            width: 100%;
            margin-bottom: 15px;
            border-collapse: collapse;
        }
        .info-table td {
            padding: 3px 0;
            vertical-align: top;
            font-size: 11px;
        }
        
        /* Tabel Item */
        .item-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 5px;
            margin-bottom: 25px;
        }
        .item-table th, .item-table td {
            border: 1px solid #777;
            padding: 6px 8px;
            text-align: left;
        }
        .item-table th {
            background-color: #f2f2f2;
            font-weight: bold;
            font-size: 11px;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }

        /* Tanda Tangan */
        .signature-table {
            width: 100%;
            margin-top: 30px;
            border-collapse: collapse;
            page-break-inside: avoid;
        }
        .signature-table td {
            width: 50%;
            text-align: center;
            vertical-align: top;
            font-size: 11px;
        }
        .signature-space {
            height: 55px;
        }
    </style>
</head>
<body>

    <!-- KOP SURAT -->
    <table class="kop-table">
        <tr>
            <td class="kop-logo">
                @if(!empty($logoBase64))
                    <img src="{{ $logoBase64 }}" style="max-height: 55px; width: auto;" alt="Logo STP">
                @else
                    <div style="font-weight: bold; font-size: 14px; border: 2px solid #000; padding: 6px; text-align: center;">STP</div>
                @endif
            </td>
            <td class="kop-text">
                <h2>SARANA TEKNIK PERKASA</h2>
                <p>Kantor Pusat : Jln. Pondok Cabe 3 Ilir</p>
                <p>Pamulang, Tangerang Selatan, Banten 15418</p>
                <p>Telp : 0622-7296905 / 081317060060</p>
                <p class="email">Email : sarana.stp@gmail.com</p>
            </td>
            <td style="width: 90px;"></td>
        </tr>
    </table>

    <div class="doc-title-container">
        <h3>BUKTI TRANSFER BARANG ANTAR GUDANG</h3>
        <p>No. Transaksi: <strong>{{ $transaksi->no_transaksi }}</strong></p>
    </div>

    <!-- INFORMASI UTAMA -->
    <table class="info-table">
        <tr>
            <td style="width: 15%;"><strong>Dari Gudang</strong></td>
            <td style="width: 2%;">:</td>
            <td style="width: 33%;">{{ $transaksi->namaAsal() }}</td>
            <td style="width: 18%;"><strong>Tanggal & Waktu</strong></td>
            <td style="width: 2%;">:</td>
            <td style="width: 30%;">{{ \Carbon\Carbon::parse($transaksi->tanggal_transaksi)->format('d/m/Y H:i') }}</td>
        </tr>
        <tr>
            <td><strong>Ke Gudang</strong></td>
            <td>:</td>
            <td>{{ $transaksi->namaTujuan() }}</td>
            <td><strong>Diminta Oleh</strong></td>
            <td>:</td>
            <td>{{ $transaksi->request_oleh ?? '-' }}</td>
        </tr>
        <tr>
            <td><strong>No. Surat Jalan</strong></td>
            <td>:</td>
            <td>{{ $transaksi->no_surat_jalan ?? '-' }}</td>
            <td><strong>Diterima Oleh (Fisik)</strong></td>
            <td>:</td>
            <td>{{ $transaksi->diterima_oleh ?? '-' }}</td>
        </tr>
        <tr>
            <td><strong>Project Terkait</strong></td>
            <td>:</td>
            <td colspan="4">{{ $transaksi->namaProjectTerkait() }}</td>
        </tr>
    </table>

    <!-- TABEL RINCIAN BARANG -->
    <table class="item-table">
        <thead>
            <tr>
                <th style="width: 5%;" class="text-center">No</th>
                <th style="width: 15%;">Kode Barang</th>
                <th style="width: 48%;">Nama Barang & Spesifikasi</th>
                <th style="width: 12%;" class="text-center">Qty</th>
                <th style="width: 20%;">Satuan</th>
            </tr>
        </thead>
        <tbody>
            @foreach($transaksi->details as $index => $detail)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td>{{ $detail->barang->kode_barang ?? '-' }}</td>
                <td>{{ $detail->barang->nama_barang ?? '-' }}</td>
                <td class="text-center">
                    @php
                        $qty = $detail->qty;
                        if (str_contains(strtolower($detail->barang->nama_barang ?? ''), 'kawat rb')) {
                            $formatted = number_format((float) $qty, 2, ',', '.');
                        } else {
                            $formatted = number_format((float) $qty, 0, ',', '.');
                        }
                        echo $formatted;
                    @endphp
                </td>
                <td>{{ $detail->satuan->nama_satuan ?? '-' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <!-- TANDA TANGAN -->
    <table class="signature-table">
        <tr>
            <td>
                <p>SPV Material Control,</p>
                <div class="signature-space"></div>
                <p><strong>( Okta Putra Arista Siboro )</strong></p>
            </td>
            <td>
                <p>Diterima Oleh,</p>
                <div class="signature-space"></div>
                <p><strong>({{ $transaksi->diterima_oleh ?? '................................' }})</strong></p>
                
                {{-- Menampilkan Jabatan --}}
                @if($jabatanPenerima)
                    <p style="margin: 2px 0 0 0;">{{ $jabatanPenerima }}</p>
                @endif
            </td>
        </tr>
    </table>

</body>
</html>