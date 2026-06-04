<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\CarbonInterval;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class ServerMonitoringController extends Controller
{
    public function index(): View
    {
        return view('server-monitoring.index');
    }

    public function metrics(): JsonResponse
    {
        return response()->json([
            'generated_at' => now()->toIso8601String(),
            'cpu' => $this->cpu(),
            'memory' => $this->memory(),
            'disk' => $this->disk(),
            'load' => $this->load(),
            'network' => $this->network(),
            'system' => $this->system(),
        ]);
    }

    /**
     * @return array{usage: int|null, cores: int|null}
     */
    private function cpu(): array
    {
        $stat = $this->cpuStat();
        $usage = null;

        if ($stat !== null) {
            $previous = Cache::get('server_monitoring.cpu');
            Cache::put('server_monitoring.cpu', $stat, now()->addMinutes(10));

            if (is_array($previous)) {
                $totalDelta = $stat['total'] - ($previous['total'] ?? 0);
                $idleDelta = $stat['idle'] - ($previous['idle'] ?? 0);

                if ($totalDelta > 0) {
                    $usage = (int) round(max(0, min(100, (1 - ($idleDelta / $totalDelta)) * 100)));
                }
            }
        }

        return [
            'usage' => $usage,
            'cores' => $this->cpuCores(),
        ];
    }

    /**
     * @return array{total: int|null, used: int|null, available: int|null, percent: int|null, swap_total: int|null, swap_used: int|null, swap_percent: int|null}
     */
    private function memory(): array
    {
        $info = $this->meminfo();

        if ($info === []) {
            return [
                'total' => null,
                'used' => null,
                'available' => null,
                'percent' => null,
                'swap_total' => null,
                'swap_used' => null,
                'swap_percent' => null,
            ];
        }

        $total = ($info['MemTotal'] ?? 0) * 1024;
        $available = ($info['MemAvailable'] ?? $info['MemFree'] ?? 0) * 1024;
        $used = max(0, $total - $available);
        $swapTotal = ($info['SwapTotal'] ?? 0) * 1024;
        $swapFree = ($info['SwapFree'] ?? 0) * 1024;
        $swapUsed = max(0, $swapTotal - $swapFree);

        return [
            'total' => $total,
            'used' => $used,
            'available' => $available,
            'percent' => $total > 0 ? (int) round(($used / $total) * 100) : null,
            'swap_total' => $swapTotal,
            'swap_used' => $swapUsed,
            'swap_percent' => $swapTotal > 0 ? (int) round(($swapUsed / $swapTotal) * 100) : null,
        ];
    }

    /**
     * @return array{total: int|null, used: int|null, free: int|null, percent: int|null}
     */
    private function disk(): array
    {
        $path = base_path();
        $total = disk_total_space($path);
        $free = disk_free_space($path);

        if ($total === false || $free === false) {
            return ['total' => null, 'used' => null, 'free' => null, 'percent' => null];
        }

        $used = max(0, $total - $free);

        return [
            'total' => $total,
            'used' => $used,
            'free' => $free,
            'percent' => $total > 0 ? (int) round(($used / $total) * 100) : null,
        ];
    }

    /**
     * @return array{one: float|null, five: float|null, fifteen: float|null}
     */
    private function load(): array
    {
        if (! function_exists('sys_getloadavg')) {
            return [
                'one' => null,
                'five' => null,
                'fifteen' => null,
            ];
        }

        $load = sys_getloadavg();

        return [
            'one' => $load[0] ?? null,
            'five' => $load[1] ?? null,
            'fifteen' => $load[2] ?? null,
        ];
    }

    /**
     * @return array{interfaces: list<array{name: string, rx: int, tx: int, rx_rate: int|null, tx_rate: int|null}>, total_rx_rate: int|null, total_tx_rate: int|null}
     */
    private function network(): array
    {
        $interfaces = $this->networkInterfaces();
        $previous = Cache::get('server_monitoring.network');
        $now = microtime(true);
        $totalRxRate = 0;
        $totalTxRate = 0;
        $hasRates = false;

        foreach ($interfaces as &$interface) {
            $interface['rx_rate'] = null;
            $interface['tx_rate'] = null;

            if (is_array($previous) && isset($previous['time'], $previous['interfaces'][$interface['name']])) {
                $elapsed = max(0.1, $now - (float) $previous['time']);
                $old = $previous['interfaces'][$interface['name']];
                $interface['rx_rate'] = max(0, (int) round(($interface['rx'] - ($old['rx'] ?? 0)) / $elapsed));
                $interface['tx_rate'] = max(0, (int) round(($interface['tx'] - ($old['tx'] ?? 0)) / $elapsed));
                $totalRxRate += $interface['rx_rate'];
                $totalTxRate += $interface['tx_rate'];
                $hasRates = true;
            }
        }

        Cache::put('server_monitoring.network', [
            'time' => $now,
            'interfaces' => collect($interfaces)
                ->mapWithKeys(fn (array $interface): array => [$interface['name'] => [
                    'rx' => $interface['rx'],
                    'tx' => $interface['tx'],
                ]])
                ->all(),
        ], now()->addMinutes(10));

        return [
            'interfaces' => $interfaces,
            'total_rx_rate' => $hasRates ? $totalRxRate : null,
            'total_tx_rate' => $hasRates ? $totalTxRate : null,
        ];
    }

    /**
     * @return array{hostname: string, os: string, php: string, laravel: string, environment: string, uptime: string|null}
     */
    private function system(): array
    {
        return [
            'hostname' => gethostname() ?: 'unknown',
            'os' => php_uname('s').' '.php_uname('r'),
            'php' => PHP_VERSION,
            'laravel' => app()->version(),
            'environment' => app()->environment(),
            'uptime' => $this->uptime(),
        ];
    }

    /**
     * @return array{idle: int, total: int}|null
     */
    private function cpuStat(): ?array
    {
        $line = @file('/proc/stat')[0] ?? null;

        if (! is_string($line) || ! str_starts_with($line, 'cpu ')) {
            return null;
        }

        $values = array_map('intval', preg_split('/\s+/', trim(substr($line, 4))) ?: []);
        $idle = ($values[3] ?? 0) + ($values[4] ?? 0);

        return [
            'idle' => $idle,
            'total' => array_sum($values),
        ];
    }

    private function cpuCores(): ?int
    {
        $content = @file_get_contents('/proc/cpuinfo');

        if (! is_string($content)) {
            return null;
        }

        preg_match_all('/^processor\s*:/m', $content, $matches);

        return count($matches[0]) ?: null;
    }

    /**
     * @return array<string, int>
     */
    private function meminfo(): array
    {
        $lines = @file('/proc/meminfo') ?: [];
        $info = [];

        foreach ($lines as $line) {
            if (preg_match('/^(\w+):\s+(\d+)/', $line, $matches) === 1) {
                $info[$matches[1]] = (int) $matches[2];
            }
        }

        return $info;
    }

    /**
     * @return list<array{name: string, rx: int, tx: int}>
     */
    private function networkInterfaces(): array
    {
        $lines = @file('/proc/net/dev') ?: [];
        $interfaces = [];

        foreach (array_slice($lines, 2) as $line) {
            [$name, $data] = array_pad(explode(':', $line, 2), 2, '');
            $name = trim($name);

            if ($name === '' || $name === 'lo') {
                continue;
            }

            $values = array_values(array_filter(preg_split('/\s+/', trim($data)) ?: [], 'strlen'));

            $interfaces[] = [
                'name' => $name,
                'rx' => (int) ($values[0] ?? 0),
                'tx' => (int) ($values[8] ?? 0),
            ];
        }

        return $interfaces;
    }

    private function uptime(): ?string
    {
        $content = @file_get_contents('/proc/uptime');

        if (! is_string($content)) {
            return null;
        }

        $seconds = (int) floor((float) explode(' ', $content)[0]);

        return CarbonInterval::seconds($seconds)->cascade()->forHumans([
            'short' => true,
            'parts' => 3,
        ]);
    }
}
