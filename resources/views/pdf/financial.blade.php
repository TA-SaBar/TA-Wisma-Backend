<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Laporan Keuangan Wisma DPR RI</title>
    <style>
        body {
            font-family: Helvetica, Arial, sans-serif;
            font-size: 11px;
            color: #333;
            margin: 0;
            padding: 20px;
        }
        .header {
            border-bottom: 3px solid #0f172a;
            padding-bottom: 15px;
            margin-bottom: 20px;
            position: relative;
        }
        .header h1 {
            margin: 0 0 5px 0;
            color: #0f172a;
            font-size: 20px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .header p {
            margin: 0;
            color: #64748b;
            font-size: 10px;
        }
        .header .meta {
            position: absolute;
            right: 0;
            top: 0;
            text-align: right;
        }
        .filters {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            padding: 10px 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        .filters table {
            width: 100%;
        }
        .filters td {
            vertical-align: top;
            font-size: 10px;
        }
        .filters strong {
            color: #0f172a;
        }
        table.data {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 25px;
        }
        table.data th, table.data td {
            border: 1px solid #cbd5e1;
            padding: 8px 10px;
            text-align: left;
        }
        table.data th {
            background-color: #0f172a;
            color: #ffffff;
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        table.data tr:nth-child(even) {
            background-color: #f8fafc;
        }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .status {
            padding: 3px 6px;
            border-radius: 3px;
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .status-lunas, .status-selesai { background-color: #d1fae5; color: #065f46; }
        .status-check_in { background-color: #dbeafe; color: #1e40af; }
        .status-pending { background-color: #fef3c7; color: #92400e; }
        .status-cancelled { background-color: #fee2e2; color: #b91c1c; }
        
        table.summary {
            width: 350px;
            float: right;
            border-collapse: collapse;
        }
        table.summary th, table.summary td {
            padding: 10px;
            border: 1px solid #cbd5e1;
        }
        table.summary th {
            background-color: #f1f5f9;
            color: #0f172a;
            text-align: left;
            font-size: 11px;
        }
        table.summary td {
            font-size: 12px;
            font-weight: bold;
            text-align: right;
        }
        .clearfix::after {
            content: "";
            clear: both;
            display: table;
        }
    </style>
</head>
<body>
    <div class="header">
        <img src="{{ public_path('images/webp/logo.webp') }}" alt="Logo" style="position: absolute; left: 0; top: 0; height: 45px;">
        <div style="margin-left: 60px;">
            <h1>Laporan Keuangan & Okupansi</h1>
            <p>Sistem Pelayanan Wisma DPR RI Kopo</p>
        </div>
        <div class="meta">
            <p><strong>Dicetak pada:</strong><br>{{ now()->translatedFormat('d F Y, H:i') }}</p>
        </div>
    </div>

    <div class="filters">
        <table>
            <tr>
                <td width="33%">
                    <strong>Periode:</strong><br>
                    @if($request->filled('start_date') && $request->filled('end_date'))
                        {{ \Carbon\Carbon::parse($request->start_date)->translatedFormat('d F Y') }} - {{ \Carbon\Carbon::parse($request->end_date)->translatedFormat('d F Y') }}
                    @else
                        Semua Periode
                    @endif
                </td>
                <td width="33%">
                    <strong>Status Transaksi:</strong><br>
                    {{ $request->filled('status') && $request->status !== 'semua' ? strtoupper($request->status) : 'SEMUA STATUS' }}
                </td>
                <td width="33%">
                    <strong>Kata Kunci (Pencarian):</strong><br>
                    {{ $request->filled('search') ? $request->search : '-' }}
                </td>
            </tr>
        </table>
    </div>

    <table class="data">
        <thead>
            <tr>
                <th width="5%" class="text-center">No</th>
                <th width="15%">Kode Booking</th>
                <th width="20%">Nama Tamu (NIP)</th>
                <th width="15%">Fasilitas</th>
                <th width="12%">Tgl Masuk</th>
                <th width="12%">Tgl Keluar</th>
                <th width="10%" class="text-center">Status</th>
                <th width="11%" class="text-right">Tarif (Rp)</th>
            </tr>
        </thead>
        <tbody>
            @php $totalAmount = 0; @endphp
            @forelse($bookings as $index => $b)
                @php 
                    if(in_array($b->status, ['lunas', 'check_in', 'selesai'])) {
                        $totalAmount += $b->total_price;
                    }
                @endphp
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td><strong>{{ $b->booking_code }}</strong></td>
                    <td>
                        {{ $b->guest_name }}<br>
                        <span style="font-size: 9px; color: #64748b;">NIP: {{ $b->guest_nip ?? '-' }}</span>
                    </td>
                    <td>{{ $b->facility->name ?? '-' }}</td>
                    <td>{{ \Carbon\Carbon::parse($b->check_in)->translatedFormat('d/m/Y') }}</td>
                    <td>{{ \Carbon\Carbon::parse($b->check_out)->translatedFormat('d/m/Y') }}</td>
                    <td class="text-center">
                        <span class="status status-{{ $b->status }}">
                            {{ $b->status }}
                        </span>
                    </td>
                    <td class="text-right">{{ number_format($b->total_price, 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="text-center" style="padding: 20px; color: #64748b;">Tidak ada data ditemukan untuk kriteria tersebut.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="clearfix">
        <table class="summary">
            <tr>
                <th>Total Transaksi Tercatat</th>
                <td>{{ $bookings->count() }}</td>
            </tr>
            <tr>
                <th>Total Pendapatan Terverifikasi (Lunas)</th>
                <td style="color: #0f172a; font-size: 14px;">Rp {{ number_format($totalAmount, 0, ',', '.') }}</td>
            </tr>
        </table>
    </div>
</body>
</html>
