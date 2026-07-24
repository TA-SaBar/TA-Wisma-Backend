<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>E-Ticket Wisma DPR RI</title>
    <style>
        body {
            font-family: Helvetica, Arial, sans-serif;
            color: #333;
            margin: 0;
            padding: 20px;
        }
        .wrapper {
            width: 100%;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
        }
        .header {
            background-color: #0f172a;
            color: #ffffff;
            padding: 20px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            letter-spacing: 2px;
            text-transform: uppercase;
        }
        .header p {
            margin: 5px 0 0 0;
            font-size: 11px;
            color: #94a3b8;
            letter-spacing: 1px;
        }
        .accent-bar {
            height: 4px;
            background-color: #f59e0b;
        }
        .content {
            padding: 30px;
            background-color: #ffffff;
        }
        table.details {
            width: 100%;
            border-collapse: collapse;
        }
        table.details td {
            padding: 12px 5px;
            vertical-align: top;
            border-bottom: 1px solid #f1f5f9;
        }
        .label {
            font-size: 11px;
            color: #64748b;
            text-transform: uppercase;
            font-weight: bold;
            width: 35%;
        }
        .value {
            font-size: 14px;
            color: #0f172a;
            font-weight: bold;
            width: 65%;
        }
        .sub-text {
            display: block;
            font-size: 11px;
            color: #94a3b8;
            font-weight: normal;
            margin-top: 2px;
        }
        .footer {
            padding: 20px 30px;
            background-color: #f8fafc;
            border-top: 1px dashed #cbd5e1;
            text-align: center;
        }
        .status-badge {
            display: inline-block;
            background-color: #10b981;
            color: #ffffff;
            padding: 8px 24px;
            border-radius: 4px;
            font-size: 14px;
            font-weight: bold;
            letter-spacing: 1px;
        }
        .status-badge.selesai { background-color: #64748b; }
        .status-badge.check_in { background-color: #3b82f6; }
        .footer-note {
            margin-top: 15px;
            font-size: 10px;
            color: #64748b;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="header" style="position: relative;">
            <img src="{{ public_path('images/logo.png') }}" alt="Logo" style="position: absolute; left: 20px; top: 15px; height: 50px;">
            <h1>Wisma DPR RI Kopo</h1>
            <p>E-Ticket / Boarding Pass Tamu Resmi</p>
        </div>
        <div class="accent-bar"></div>
        <div class="content">
            <table class="details">
                <tr>
                    <td class="label" style="padding-top: 0;">Kode Booking</td>
                    <td class="value" style="padding-top: 0; font-size: 18px; color: #0f172a;">{{ $booking->booking_code }}</td>
                </tr>
                <tr>
                    <td class="label">Nama Tamu</td>
                    <td class="value">
                        {{ $booking->guest_name }}
                        <span class="sub-text">NIP: {{ $booking->guest_nip ?? '-' }}</span>
                    </td>
                </tr>
                <tr>
                    <td class="label">Unit Fasilitas</td>
                    <td class="value">
                        {{ $booking->facility->name ?? '-' }}
                        <span class="sub-text">{{ $booking->facility->area ?? '-' }}</span>
                    </td>
                </tr>
                <tr>
                    <td class="label">Jadwal Kedatangan</td>
                    <td class="value">
                        {{ \Carbon\Carbon::parse($booking->check_in)->translatedFormat('l, d F Y') }}
                        <span class="sub-text">{{ ($booking->facility->unit == 'night') ? 'Waktu Check-in: Mulai 14:00 WIB' : ''}}</span> 
                    </td>
                </tr>
                <tr>
                    <td class="label">Jadwal Kepulangan</td>
                    <td class="value">
                        {{ \Carbon\Carbon::parse($booking->check_out)->translatedFormat('l, d F Y') }}
                        <span class="sub-text">{{ ($booking->facility->unit == 'night') ? 'Waktu Check-out: Maksimal 12:00 WIB' : ''}}</span> 
                    </td>
                </tr>
                <tr>
                    <td class="label" style="border-bottom: none;">Durasi Menginap</td>
                    <td class="value" style="border-bottom: none;">{{ $booking->nights }} {{ ($booking->facility->unit ?? 'night') === 'day' ? 'Hari' : 'Malam' }}</td>
                </tr>
            </table>
        </div>
        <div class="footer">
            <div class="status-badge {{ $booking->status }}">
                {{ $booking->status === 'lunas' ? 'LUNAS (PAID)' : strtoupper($booking->status) }}
            </div>
            <div class="footer-note">Tunjukkan dokumen ini kepada Resepsionis saat melakukan proses Check-In di Front Office.</div>
        </div>
    </div>
</body>
</html>
