<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Laporan Rekapitulasi Keluhan Tamu</title>
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
        .status {
            padding: 3px 6px;
            border-radius: 3px;
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .status-selesai { background-color: #d1fae5; color: #065f46; }
        .status-diproses { background-color: #dbeafe; color: #1e40af; }
        .status-pending { background-color: #fef3c7; color: #92400e; }
        
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
        <img src="{{ public_path('images/logo.png') }}" alt="Logo" style="position: absolute; left: 0; top: 0; height: 45px;">
        <div style="margin-left: 60px;">
            <h1>LAPORAN REKAPITULASI KELUHAN TAMU</h1>
            <p>Sistem Pelayanan Wisma DPR RI Kopo</p>
        </div>
        
        <div class="meta">
            <p><strong>Dicetak Tanggal:</strong> {{ date('d/m/Y H:i') }}</p>
            <p><strong>Total Keluhan:</strong> {{ count($complaints) }}</p>
        </div>
    </div>

    <table class="data">
        <thead>
            <tr>
                <th class="text-center" width="5%">No</th>
                <th width="15%">Kode Keluhan</th>
                <th width="15%">Tanggal Lapor</th>
                <th width="15%">Nama Tamu</th>
                <th width="25%">Kategori & Judul</th>
                <th width="15%">Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($complaints as $index => $c)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td><strong>{{ $c->complaint_code }}</strong></td>
                <td>{{ \Carbon\Carbon::parse($c->created_at)->format('d M Y H:i') }}</td>
                <td>{{ $c->user ? $c->user->name : '-' }}</td>
                <td>
                    <strong>{{ ucfirst($c->category) }}</strong><br>
                    {{ $c->title }}
                </td>
                <td>
                    @if($c->status === 'pending')
                        <span class="status status-pending">Menunggu</span>
                    @elseif($c->status === 'diproses')
                        <span class="status status-diproses">Diproses</span>
                    @elseif($c->status === 'selesai')
                        <span class="status status-selesai">Selesai</span>
                    @else
                        <span class="status">{{ $c->status }}</span>
                    @endif
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="6" class="text-center">Tidak ada data keluhan pada kriteria ini.</td>
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
