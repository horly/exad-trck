<?php

namespace App\Http\Controllers;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class MobileDownloadController extends Controller
{
    public function index(): View
    {
        $releases = $this->availableReleases();
        abort_if($releases->isEmpty(), 503);

        return view('mobile-download.index', [
            'currentRelease' => $releases->first(),
            'archivedReleases' => $releases->skip(1),
        ]);
    }

    public function download(string $release): BinaryFileResponse
    {
        $releaseData = collect(config('mobile_releases.android.releases'))
            ->firstWhere('slug', $release);

        abort_if($releaseData === null, 404);
        abort_unless(Storage::disk('local')->exists($releaseData['path']), 404);

        return response()->download(
            Storage::disk('local')->path($releaseData['path']),
            $releaseData['filename'],
            [
                'Cache-Control' => 'public, max-age=86400, immutable',
                'Content-Type' => 'application/vnd.android.package-archive',
                'X-Content-Type-Options' => 'nosniff',
            ],
        );
    }

    private function availableReleases(): Collection
    {
        return collect(config('mobile_releases.android.releases'))
            ->filter(fn (array $release): bool => Storage::disk('local')->exists($release['path']))
            ->sortByDesc('build')
            ->map(fn (array $release): array => [
                ...$release,
                'full_version' => $release['version'].'+'.$release['build'],
                'size' => sprintf('%.1f MB', Storage::disk('local')->size($release['path']) / 1024 / 1024),
                'formatted_date' => Carbon::parse($release['date'])->translatedFormat('d M Y'),
            ])
            ->values();
    }
}
