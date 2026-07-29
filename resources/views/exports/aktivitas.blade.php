<!DOCTYPE html>
<html>
<head>
    <title>Laporan Aktivitas Publik</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
        }
        h2 {
            text-align: center;
        }
        .filter-info {
            margin-bottom: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            border: 1px solid #000;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        .group-title {
            background-color: #e2e8f0;
            font-weight: bold;
            padding: 10px;
            margin-top: 20px;
            margin-bottom: 5px;
            border: 1px solid #000;
        }
    </style>
</head>
<body>
    <h2>Laporan Aktivitas Publik</h2>
    
    <div class="filter-info">
        @if($startDate || $endDate)
            <p><strong>Periode:</strong> {{ $startDate ?: 'Semua' }} s/d {{ $endDate ?: 'Semua' }}</p>
        @endif
        @if($typeFilter)
            <p><strong>Filter Jenis:</strong> {{ ucwords(str_replace('_', ' ', $typeFilter)) }}</p>
        @endif
        @if($kelasName)
            <p><strong>Filter Kelas:</strong> {{ $kelasName }}</p>
        @endif
    </div>

    @if($groupBy)
        @foreach($data as $type => $group)
            <div class="group-title">
                @php
                    $label = $type;
                    if ($type == 'pembayaran_lunas') $label = 'Pembayaran Tagihan (Lunas)';
                    elseif ($type == 'pendaftaran_lunas') $label = 'Pendaftaran (Lunas)';
                    elseif ($type == 'pendaftaran_pending') $label = 'Pendaftaran (Menunggu)';
                    elseif ($type == 'cuti_disetujui') $label = 'Cuti Disetujui';
                    elseif ($type == 'siswa_resign') $label = 'Siswa Resign';
                @endphp
                Grup: {{ $label }} ({{ count($group) }} Aktivitas)
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Waktu</th>
                        <th>Aktivitas</th>
                        <th>Keterangan</th>
                        <th>Siswa / Kelas</th>
                        <th>Nominal</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($group as $act)
                        <tr>
                            <td>{{ \Carbon\Carbon::parse($act->date)->format('Y-m-d H:i') }}</td>
                            <td>{{ $act->title }}</td>
                            <td>{{ $act->description }}</td>
                            <td>
                                {{ $act->nama_siswa }}<br>
                                <span style="font-size: 0.85em; color: #555;">{{ $act->nama_kelas }}</span>
                            </td>
                            <td>{{ $act->amount ? 'Rp ' . number_format($act->amount, 0, ',', '.') : '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endforeach
    @else
        <table>
            <thead>
                <tr>
                    <th>Waktu</th>
                    <th>Aktivitas</th>
                    <th>Keterangan</th>
                    <th>Siswa / Kelas</th>
                    <th>Nominal</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data as $act)
                    <tr>
                        <td>{{ \Carbon\Carbon::parse($act->date)->format('Y-m-d H:i') }}</td>
                        <td>{{ $act->title }}</td>
                        <td>{{ $act->description }}</td>
                        <td>
                            {{ $act->nama_siswa }}<br>
                            <span style="font-size: 0.85em; color: #555;">{{ $act->nama_kelas }}</span>
                        </td>
                        <td>{{ $act->amount ? 'Rp ' . number_format($act->amount, 0, ',', '.') : '-' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</body>
</html>
