<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Bukti Peminjaman Alat #{{ $peminjaman->id }}</title>
    <style>
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 11px;
            color: #222;
            line-height: 1.4;
        }
        /* KOP SURAT */
        .kop-table {
            width: 100%;
            border-collapse: collapse;
            border-bottom: 3px double #000;
            padding-bottom: 8px;
            margin-bottom: 15px;
        }
        .kop-table td { vertical-align: middle; }
        .kop-logo { width: 90px; text-align: left; }
        .kop-text { text-align: center; }
        .kop-text h2 { margin: 0; font-size: 18px; font-weight: bold; text-transform: uppercase; }
        .kop-text p { margin: 2px 0; font-size: 10px; }
        .kop-text .email { font-style: italic; color: #1a0dab; }

        /* JUDUL DOKUMEN */
        .doc-title { text-align: center; margin-bottom: 15px; }
        .doc-title h3 { margin: 0; font-size: 14px; text-decoration: underline; text-transform: uppercase; }
        .doc-title p { margin: 3px 0 0 0; font-size: 11px; font-weight: bold; }

        /* INFORMASI PEMINJAMAN */
        .info-table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        .info-table td { padding: 4px; vertical-align: top; font-size: 11px; }

        /* TABEL DETAIL ALAT */
        .detail-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .detail-table th, .detail-table td { border: 1px solid #333; padding: 7px 8px; font-size: 11px; }
        .detail-table th { background-color: #f0f0f0; text-transform: uppercase; font-weight: bold; text-align: center; }

        /* TANDA TANGAN */
        .ttd-table { width: 100%; border-collapse: collapse; margin-top: 30px; }
        .ttd-table td { text-align: center; vertical-align: top; width: 33%; font-size: 11px; }
        .space-ttd { height: 55px; }
    </style>
</head>
<body>

    @php
        $possiblePaths = [
            public_path('images/logo-stp.png'),
            public_path('images/logo-stp.PNG'),
            public_path('images/logo-stp.png'),
        ];

        $logoBase64 = null;
        foreach ($possiblePaths as $path) {
            if (file_exists($path)) {
                $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
                $mime = ($ext === 'jpg' || $ext === 'jpeg') ? 'image/jpeg' : 'image/png';
                $logoBase64 = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($path));
                break;
            }
        }
    @endphp

    <!-- KOP SURAT -->
    <table class="kop-table">
        <tr>
            <td class="kop-logo">
                @if($logoBase64)
                    <img src="{{ $logoBase64 }}" style="max-height: 65px; width: auto;" alt="Logo STP">
                @else
                    <div style="font-weight: bold; font-size: 16px; border: 2px solid #000; padding: 8px;">STP</div>
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

    <!-- JUDUL -->
    <div class="doc-title">
        <h3>BUKTI PEMINJAMAN ALAT</h3>
        <p>NO: PINJAM/{{ \Carbon\Carbon::parse($peminjaman->tanggal_pinjam)->format('Ymd') }}/{{ str_pad($peminjaman->id, 4, '0', STR_PAD_LEFT) }}</p>
    </div>

    <!-- INFORMASI PENANGGUNG JAWAB & JADWAL -->
    <table class="info-table">
        <tr>
            <td width="18%"><strong>Nama Peminjam</strong></td>
            <td width="32%">: {{ $peminjaman->karyawan?->nama ?? '-' }}</td>
            <td width="18%"><strong>Tgl & Jam Pinjam</strong></td>
            <td width="32%">: {{ \Carbon\Carbon::parse($peminjaman->tanggal_pinjam)->translatedFormat('d F Y H:i') }} WIB</td>
        </tr>
        <tr>
            <td><strong>Ambil dari Gudang</strong></td>
            <td>: {{ $peminjaman->gudang?->nama_gudang ?? '-' }}</td>
            <td><strong>Rencana Kembali</strong></td>
            <td>: {{ $peminjaman->tanggal_rencana_kembali ? \Carbon\Carbon::parse($peminjaman->tanggal_rencana_kembali)->translatedFormat('d F Y') : '-' }}</td>
        </tr>
        <tr>
            <td><strong>Keperluan / Area</strong></td>
            <td>: {{ $peminjaman->keperluan ?? '-' }}</td>
            <td><strong>Status Dikembalikan</strong></td>
            <td>: {{ $peminjaman->tanggal_aktual_kembali ? \Carbon\Carbon::parse($peminjaman->tanggal_aktual_kembali)->translatedFormat('d F Y H:i') . ' WIB' : 'Masih Dipinjam' }}</td>
        </tr>
    </table>

    <!-- TABEL DETAIL ALAT -->
    <table class="detail-table">
        <thead>
            <tr>
                <th width="8%">NO</th>
                <th width="22%">KODE ALAT</th>
                <th width="45%">NAMA ALAT KERJA</th>
                <th width="12%">QTY</th>
                <th width="13%">SATUAN</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td style="text-align: center;">1</td>
                <td style="text-align: center;">{{ $peminjaman->alat?->kode_alat ?? '-' }}</td>
                <td>{{ $peminjaman->alat?->nama_alat ?? '-' }}</td>
                <td style="text-align: center; font-weight: bold;">{{ $peminjaman->qty }}</td>
                <td style="text-align: center;">{{ is_object($peminjaman->alat?->satuan) ? $peminjaman->alat->satuan->nama_satuan : ($peminjaman->alat?->satuan ?? 'Unit') }}</td>
            </tr>
        </tbody>
    </table>

    @if($peminjaman->catatan)
        <p style="font-size: 10px; margin-top: -10px; margin-bottom: 15px;">
            <strong>Catatan Tambahan:</strong> {{ $peminjaman->catatan }}
        </p>
    @endif

    <!-- TANDA TANGAN 3 PIHAK -->
    <table class="ttd-table">
        <tr>
            <td>
                Yang Meminjam,<br>
                <strong>(Peminjam)</strong>
                <div class="space-ttd"></div>
                ( <strong>{{ $peminjaman->karyawan?->nama ?? '....................................' }}</strong> )
            </td>
            <td>
                Petugas Gudang,<br>
                <strong>(Pengeluaran Alat)</strong>
                <div class="space-ttd"></div>
                ( .................................... )
            </td>
            <td>
                Mengetahui,<br>
                <strong>(SPV Material Control)</strong>
                <div class="space-ttd"></div>
                ( .................................... )
            </td>
        </tr>
    </table>

</body>
</html>