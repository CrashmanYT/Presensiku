<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Guru Terlambat - {{ $dateTitle }}</title>
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 12px; color: #222; }
        h1 { font-size: 18px; margin: 0 0 8px 0; }
        .meta { margin-bottom: 12px; color: #555; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ccc; padding: 6px 8px; text-align: left; }
        th { background: #f5f5f5; }
        .right { text-align: right; }
    </style>
</head>
<body>
    <h1>Laporan Guru Terlambat</h1>
    <div class="meta">Tanggal: <strong>{{ $dateTitle }}</strong></div>

    <table>
        <thead>
            <tr>
                <th style="width:40px">No</th>
                <th>Nama</th>
                <th style="width:120px">NIP</th>
                <th style="width:90px">Jam Masuk</th>
                <th style="width:120px" class="right">Menit Terlambat</th>
            </tr>
        </thead>
        <tbody>
        @foreach($rows as $row)
            <tr>
                <td>{{ $row['no'] }}</td>
                <td>{{ $row['name'] }}</td>
                <td>{{ $row['nip'] }}</td>
                <td>{{ $row['time_in'] }}</td>
                <td class="right">{{ $row['minutes_late'] }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</body>
</html>
