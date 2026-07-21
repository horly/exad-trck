<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <title>{{ $title }} - {{ $applicationSettings->app_name }}</title>
    <style>
        @page { margin: 18mm 14mm; }
        body { font-family: DejaVu Sans, sans-serif; color: #0b1220; margin: 0; font-size: 11px; }
        header { border-bottom: 1px solid #dbe3ef; padding-bottom: 14px; margin-bottom: 18px; }
        h1 { margin: 0 0 7px; font-size: 21px; line-height: 1.15; color: #171064; }
        p { margin: 0; color: #64748b; font-size: 10px; }
        table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        th { text-align: left; text-transform: uppercase; letter-spacing: .04em; color: #475569; background: #f4f7fb; }
        th, td { border-bottom: 1px solid #dbe3ef; padding: 9px 8px; vertical-align: top; word-wrap: break-word; }
        strong { display: block; color: #0f172a; }
        small { display: block; margin-top: 3px; color: #64748b; }
        .brand { margin-bottom: 5px; font-size: 10px; font-weight: 800; letter-spacing: .08em; color: #171064; text-transform: uppercase; }
    </style>
</head>
<body>
    <header>
        <div>
            <p class="brand">{{ $applicationSettings->app_name }}</p>
            <h1>{{ $title }}</h1>
            <p>{{ __('reports.print_period', ['from' => $filters['date_from']->format('Y-m-d'), 'to' => $filters['date_to']->format('Y-m-d')]) }}</p>
        </div>
    </header>

    <table>
        <thead>
            <tr>
                @foreach ($columns as $label)
                    <th>{{ $label }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                @if ($row instanceof \App\Models\Position)
                    <tr>
                        <td>{{ $row->id }}</td>
                        @if ($showTechnicalDetails)
                            <td><strong>{{ $row->device?->name ?: $row->imei }}</strong><small>{{ $row->device?->imei ?: $row->imei }}</small></td>
                        @endif
                        <td>{{ $row->device?->vehicle?->name ?: '-' }}</td>
                        <td>{{ $row->device?->fleet?->name ?: '-' }}</td>
                        <td>{{ $row->speed ?? 0 }} km/h</td>
                        <td>{{ $row->address ?: '-' }}</td>
                        <td>{{ $row->server_time?->format('Y-m-d H:i:s') ?: '-' }}</td>
                    </tr>
                @elseif ($row instanceof \App\Models\TrackerEvent)
                    <tr>
                        <td>{{ $row->id }}</td>
                        <td><strong>{{ $row->localizedTitle() }}</strong><small>{{ $row->localizedMessage() }}</small></td>
                        <td>{{ $row->vehicle?->name ?: '-' }}</td>
                        @if ($showTechnicalDetails)
                            <td>{{ $row->device?->name ?: $row->device?->imei ?: '-' }}</td>
                        @endif
                        <td>{{ $row->fleet?->name ?: '-' }}</td>
                        <td>{{ $row->durationLabel() ?: '-' }}</td>
                        <td>{{ $row->started_at?->format('Y-m-d H:i:s') ?: '-' }}</td>
                    </tr>
                @elseif ($row instanceof \App\Models\Alert)
                    <tr>
                        <td>{{ $row->id }}</td>
                        <td><strong>{{ $row->localizedTitle() }}</strong><small>{{ $row->localizedMessageFor(auth()->user()) }}</small></td>
                        <td>{{ $row->severity }}</td>
                        <td>{{ $row->vehicle?->name ?: '-' }}</td>
                        <td>{{ $row->fleet?->name ?: '-' }}</td>
                        <td>{{ $row->status }}</td>
                        <td>{{ $row->occurred_at?->format('Y-m-d H:i:s') ?: '-' }}</td>
                    </tr>
                @else
                    <tr>
                        <td>{{ $row->id }}</td>
                        <td><strong>{{ $row->name }}</strong><small>{{ $row->code ?: '-' }}</small></td>
                        <td>{{ $row->vehicles_count }}</td>
                        @if ($showTechnicalDetails)
                            <td>{{ $row->devices_count }}</td>
                            <td>{{ $row->online_devices_count }}</td>
                            <td>{{ $row->offline_devices_count }}</td>
                        @endif
                        <td>{{ $row->status }}</td>
                    </tr>
                @endif
            @empty
                <tr>
                    <td colspan="{{ count($columns) }}">{{ __('reports.empty') }}</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
