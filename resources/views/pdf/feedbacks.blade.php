<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Laporan Ulasan dan Penilaian Tamu</title>
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
        .text-center { text-align: center; }
        
        .footer {
            margin-top: 50px;
            width: 100%;
        }
        .footer td {
            width: 50%;
            text-align: center;
        }
        .sign-area {
            height: 80px;
        }
    </style>
</head>
<body>

    <div class="header">
        <img src="{{ public_path('images/webp/logo.webp') }}" alt="Logo" style="position: absolute; left: 0; top: 0; height: 45px;">
        <div style="margin-left: 60px;">
            <h1>LAPORAN ULASAN & PENILAIAN TAMU</h1>
            <p>Sistem Pelayanan Wisma DPR RI Kopo</p>
        </div>
        
        <div class="meta">
            <p><strong>Dicetak pada:</strong><br>{{ now()->translatedFormat('d F Y, H:i') }}</p>
        </div>
    </div>

    <div class="filters">
        <table>
            <tr>
                <td width="50%">
                    <strong>Periode:</strong><br>
                    @if($request->filled('start_date') && $request->filled('end_date'))
                        {{ \Carbon\Carbon::parse($request->start_date)->translatedFormat('d F Y') }} - {{ \Carbon\Carbon::parse($request->end_date)->translatedFormat('d F Y') }}
                    @else
                        Semua Periode
                    @endif
                </td>
                <td width="50%">
                    <strong>Total Ulasan:</strong><br>
                    {{ count($feedbacks) }}
                </td>
            </tr>
        </table>
    </div>

    <div class="filters">
        <table>
            <tr>
                <td width="25%"><strong>Rata-rata Rating (Semua):</strong> {{ number_format($aggregation['avg_overall'], 1) }} / 5</td>
                <td width="25%"><strong>Kebersihan:</strong> {{ number_format($aggregation['avg_cleanliness'], 1) }} / 5</td>
                <td width="25%"><strong>Fasilitas:</strong> {{ number_format($aggregation['avg_facilities'], 1) }} / 5</td>
                <td width="25%"><strong>Pelayanan:</strong> {{ number_format($aggregation['avg_service'], 1) }} / 5</td>
            </tr>
        </table>
    </div>

    <table class="data">
        <thead>
            <tr>
                <th class="text-center" width="5%">No</th>
                <th width="15%">Tanggal Ulasan</th>
                <th width="15%">Nama Tamu</th>
                <th width="20%">Booking</th>
                <th class="text-center" width="10%">Rating</th>
                <th width="35%">Komentar</th>
            </tr>
        </thead>
        <tbody>
            @forelse($feedbacks as $index => $f)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td>{{ \Carbon\Carbon::parse($f->created_at)->format('d M Y H:i') }}</td>
                <td>{{ $f->user ? $f->user->name : '-' }}</td>
                <td>{{ $f->booking ? $f->booking->booking_code : '-' }}</td>
                <td class="text-center"><strong>{{ number_format($f->average_rating, 1) }} / 5</strong></td>
                <td>{{ $f->comment ?? '-' }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="6" class="text-center">Tidak ada data ulasan pada kriteria ini.</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <table class="footer">
        <tr>
            <td></td>
            <td>
                <p>Mengetahui,</p>
                <p><strong>Customer Service</strong></p>
                <div class="sign-area"></div>
                <p>(................................................)</p>
            </td>
        </tr>
    </table>

</body>
</html>
