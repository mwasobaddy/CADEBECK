<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $title ?? 'Export' }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 9px; margin: 20px; }
        h2 { text-align: center; margin-bottom: 15px; font-size: 14px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th { background: #4f46e5; color: white; padding: 6px 4px; text-align: left; font-size: 8px; }
        td { padding: 4px; border-bottom: 1px solid #ddd; }
        tr:nth-child(even) td { background: #f9fafb; }
        .footer { text-align: center; margin-top: 15px; font-size: 7px; color: #888; }
    </style>
</head>
<body>
    <h2>{{ $title }}</h2>
    <table>
        <thead>
            <tr>
                @foreach($headers as $header)
                    <th>{{ $header }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach($rows as $row)
                <tr>
                    @foreach($row as $cell)
                        <td>{{ $cell }}</td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>
    <div class="footer">{{ __('Generated on') }}: {{ now()->format('Y-m-d H:i:s') }}</div>
</body>
</html>
