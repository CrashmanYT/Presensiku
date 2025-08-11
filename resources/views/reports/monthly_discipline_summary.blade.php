<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ringkasan Disiplin Bulanan — {{ $monthTitle }}</title>
    <style>
        body { font-family: DejaVu Sans, Arial, Helvetica, sans-serif; font-size: 12px; color: #111; }
        h1 { font-size: 18px; margin: 0 0 10px 0; }
        .meta { font-size: 11px; color: #555; margin-bottom: 12px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ccc; padding: 6px 8px; text-align: left; }
        th { background: #f2f2f2; }
        tfoot td { font-size: 11px; color: #333; }
    </style>
</head>
<body>
    <h1>Ringkasan Disiplin Bulanan — {{ $monthTitle }}</h1>
    <div class="meta">
        Kriteria: L &gt;= {{ (int)($thresholds['min_total_late'] ?? 0) }},
        A &gt;= {{ (int)($thresholds['min_total_absent'] ?? 0) }},
        Skor &lt;= {{ (int)($thresholds['min_score'] ?? 0) }}.
        Batas: {{ (int)($limit ?? 0) }} siswa.
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 40px;">No</th>
                <th>Nama</th>
                <th style="width: 120px;">Kelas</th>
                <th style="width: 80px;">Terlambat</th>
                <th style="width: 80px;">Alpa</th>
                <th style="width: 80px;">Skor</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $row)
                <tr>
                    <td>{{ $row['no'] }}</td>
                    <td>{{ $row['name'] }}</td>
                    <td>{{ $row['class'] }}</td>
                    <td>{{ $row['late'] }}</td>
                    <td>{{ $row['absent'] }}</td>
                    <td>{{ $row['score'] }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" style="text-align:center; color:#666;">Tidak ada data untuk bulan ini.</td>
                </tr>
            @endforelse
        </tbody>
        @if(($extraCount ?? 0) > 0)
        <tfoot>
            <tr>
                <td colspan="6">(+{{ (int)$extraCount }} siswa lainnya melebihi ambang, tidak ditampilkan karena melewati batas ringkasan)</td>
            </tr>
        </tfoot>
        @endif
    </table>
</body>
</html>
