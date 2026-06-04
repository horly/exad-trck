<?php

return [
    'title' => 'Server logs',
    'eyebrow' => 'MONITORING',
    'breadcrumb' => 'ADMIN / SERVER LOGS',
    'tabs_label' => 'Server log files',
    'logs' => [
        'gps-tcp' => 'GPS TCP',
        'gps-tcp-error' => 'TCP errors',
        'gps-udp' => 'GPS UDP',
        'gps-udp-error' => 'UDP errors',
        'gps-tcpdump' => 'TCP capture',
        'laravel' => 'Laravel',
    ],
    'lines' => 'Lines',
    'refresh' => 'Refresh',
    'pause' => 'Pause',
    'resume' => 'Resume',
    'live' => 'Live',
    'paused' => 'Paused',
    'waiting' => 'Waiting for first load',
    'loading' => 'Loading logs...',
    'loading_error' => 'Unable to load logs',
    'file_missing' => 'Log file not found on this server.',
];
