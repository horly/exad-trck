<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('local');

    config(['mobile_releases.android' => [
        'minimum_version' => 'Android 7.0',
        'releases' => [
            [
                'slug' => '1-0-0-build-10',
                'version' => '1.0.0',
                'build' => 10,
                'date' => '2026-09-01',
                'filename' => 'EXAD-Tracking-1.0.0+10.apk',
                'path' => 'mobile-releases/android/EXAD-Tracking-1.0.0+10.apk',
                'sha256' => 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa',
                'summary_key' => 'downloads.release_10_summary',
            ],
            [
                'slug' => '1-0-0-build-9',
                'version' => '1.0.0',
                'build' => 9,
                'date' => '2026-08-08',
                'filename' => 'EXAD-Tracking-1.0.0+9.apk',
                'path' => 'mobile-releases/android/EXAD-Tracking-1.0.0+9.apk',
                'sha256' => 'bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb',
                'summary_key' => 'downloads.archive_summary',
            ],
        ],
    ]]);

    Storage::disk('local')->put('mobile-releases/android/EXAD-Tracking-1.0.0+10.apk', 'latest-apk');
    Storage::disk('local')->put('mobile-releases/android/EXAD-Tracking-1.0.0+9.apk', 'archive-apk');
});

test('the android download page is public and lists current and archived releases', function () {
    $this->get(route('mobile.downloads.index'))
        ->assertSuccessful()
        ->assertSee('Votre flotte vous accompagne partout.')
        ->assertSeeInOrder(['Version 1.0.0+10', 'Build 10'])
        ->assertSeeInOrder(['Version 1.0.0+9', 'Build 9'])
        ->assertSee('aria-label="Télécharger la version 1.0.0+10, build 10"', false)
        ->assertSee('aria-label="Télécharger la version 1.0.0+9, build 9"', false)
        ->assertSee('aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa')
        ->assertSee('bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb')
        ->assertSee('signature Android de recette')
        ->assertSee(route('login'), false)
        ->assertSee(route('mobile.downloads.android', '1-0-0-build-10'), false);
});

test('the login page links to the public android download page', function () {
    $this->get(route('login'))
        ->assertSuccessful()
        ->assertSee('Télécharger l’application')
        ->assertSee(route('mobile.downloads.index'), false);
});

test('the android download page supports english', function () {
    $this->withSession(['locale' => 'en'])
        ->get(route('mobile.downloads.index'))
        ->assertSuccessful()
        ->assertSee('Your fleet goes everywhere with you.')
        ->assertSeeInOrder(['Version 1.0.0+10', 'Build 10'])
        ->assertSeeInOrder(['Version 1.0.0+9', 'Build 9'])
        ->assertSee('aria-label="Download version 1.0.0+10, build 10"', false)
        ->assertSee('aria-label="Download version 1.0.0+9, build 9"', false)
        ->assertSee('Previous versions')
        ->assertSee('Download APK');
});

test('a declared android release is downloaded with safe headers', function () {
    $this->get(route('mobile.downloads.android', '1-0-0-build-10'))
        ->assertSuccessful()
        ->assertDownload('EXAD-Tracking-1.0.0+10.apk')
        ->assertHeader('content-type', 'application/vnd.android.package-archive')
        ->assertHeader('x-content-type-options', 'nosniff')
        ->assertHeader('cache-control', 'immutable, max-age=86400, public');
});

test('unknown missing and malformed android releases are rejected', function () {
    $this->get('/application/android/unknown-release')->assertNotFound();

    Storage::disk('local')->delete('mobile-releases/android/EXAD-Tracking-1.0.0+9.apk');
    $this->get(route('mobile.downloads.android', '1-0-0-build-9'))->assertNotFound();

    $this->get('/application/android/INVALID_RELEASE')->assertNotFound();
});

test('the download page reports unavailability when no apk is published', function () {
    Storage::fake('local');

    $this->get(route('mobile.downloads.index'))->assertServiceUnavailable();
});
