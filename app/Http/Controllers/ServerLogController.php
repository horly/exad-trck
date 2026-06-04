<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ServerLogController extends Controller
{
    /**
     * @var array<string, array{file: string, icon: string}>
     */
    private const LOGS = [
        'gps-tcp' => ['file' => 'gps-tcp.log', 'icon' => 'fa-network-wired'],
        'gps-tcp-error' => ['file' => 'gps-tcp-error.log', 'icon' => 'fa-triangle-exclamation'],
        'gps-udp' => ['file' => 'gps-udp.log', 'icon' => 'fa-satellite-dish'],
        'gps-udp-error' => ['file' => 'gps-udp-error.log', 'icon' => 'fa-circle-exclamation'],
        'gps-tcpdump' => ['file' => 'gps-tcpdump.log', 'icon' => 'fa-terminal'],
        'laravel' => ['file' => 'laravel.log', 'icon' => 'fa-file-lines'],
    ];

    public function index(Request $request): View
    {
        $selected = $this->selectedLog($request);

        return view('server-logs.index', [
            'logs' => $this->logs(),
            'selected' => $selected,
            'defaultLines' => 300,
        ]);
    }

    public function content(Request $request): JsonResponse
    {
        $selected = $this->selectedLog($request);
        $lines = max(50, min((int) $request->integer('lines', 300), 1500));
        $path = $this->pathFor($selected);

        if (! is_file($path)) {
            return response()->json([
                'exists' => false,
                'content' => '',
                'size' => '0 B',
                'updated_at' => null,
                'message' => __('server_logs.file_missing'),
            ]);
        }

        return response()->json([
            'exists' => true,
            'content' => $this->tail($path, $lines),
            'size' => $this->fileSize(filesize($path) ?: 0),
            'updated_at' => Carbon::createFromTimestamp(filemtime($path) ?: time())->diffForHumans(),
            'message' => __('server_logs.live'),
        ]);
    }

    /**
     * @return array<string, array{label: string, file: string, icon: string}>
     */
    private function logs(): array
    {
        return collect(self::LOGS)
            ->map(fn (array $log, string $key): array => [
                ...$log,
                'label' => __("server_logs.logs.{$key}"),
            ])
            ->all();
    }

    private function selectedLog(Request $request): string
    {
        $selected = $request->string('log', 'gps-tcp')->toString();

        return array_key_exists($selected, self::LOGS) ? $selected : 'gps-tcp';
    }

    private function pathFor(string $log): string
    {
        return storage_path('logs/'.self::LOGS[$log]['file']);
    }

    private function fileSize(int $bytes): string
    {
        if ($bytes < 1024) {
            return "{$bytes} B";
        }

        $units = ['KB', 'MB', 'GB'];
        $size = $bytes / 1024;

        foreach ($units as $index => $unit) {
            if ($size < 1024 || $index === array_key_last($units)) {
                return round($size, 1).' '.$unit;
            }

            $size /= 1024;
        }

        return "{$bytes} B";
    }

    private function tail(string $path, int $lines): string
    {
        $handle = fopen($path, 'rb');

        if ($handle === false) {
            return '';
        }

        try {
            $size = filesize($path) ?: 0;
            $offset = max(0, $size - 1024 * 512);

            if ($offset > 0) {
                fseek($handle, $offset);
                fgets($handle);
            }

            $content = stream_get_contents($handle) ?: '';
            $contentLines = preg_split("/\r\n|\n|\r/", $content) ?: [];
            $content = implode(PHP_EOL, array_slice($contentLines, -$lines));

            return Str::of($content)->replace("\0", '')->toString();
        } finally {
            fclose($handle);
        }
    }
}
