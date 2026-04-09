<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Tickets Export</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ccc; padding: 6px; text-align: left; }
        th { background-color: #f5f5f5; }
        h2, p { text-align: center; margin: 0; }
    </style>
</head>
<body>
    <h2>{{ $status }} IT Support Tickets Report ({{ $range }})</h2>
    <p>Generated on {{ $date }}</p>

    <table>
        <thead>
            <tr>
                {{-- <th>ID</th> --}}
                <th>Employee</th>
                <th>Topic</th>
                <th>IT Tech</th>
                <th>Created</th>
                <th>Resolved</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($tickets as $t)
            <tr>
                {{-- <td>{{ $t->id }}</td> --}}
                <td>{{ $t->employee_name }}</td>
                <td>{{ $t->topic }}</td>
                <td>{{ $t->it_tech_name ?? '-' }}</td>
                <td>{{ $t->created_at->format('Y-m-d') }}</td>
                <td>{{ $t->date_resolved ? $t->date_resolved->format('Y-m-d') : '-' }}</td>
                <td>{{ $t->status }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
