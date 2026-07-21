<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdatePersonalProfileRequest;
use App\Http\Requests\UpdateProfileEmailRequest;
use App\Http\Requests\UpdateProfilePhotoRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function show(): View
    {
        return view('profile.show');
    }

    public function updatePersonal(UpdatePersonalProfileRequest $request): RedirectResponse
    {
        $request->user()->update($request->validated());

        return to_route('profile.show')->with('profile_status', 'personal-updated');
    }

    public function updateEmail(UpdateProfileEmailRequest $request): RedirectResponse
    {
        $request->user()->forceFill([
            'email' => $request->validated('email'),
        ])->save();

        return to_route('profile.show')->with('profile_status', 'email-updated');
    }

    public function updatePhoto(UpdateProfilePhotoRequest $request): RedirectResponse
    {
        $user = $request->user();
        $oldPath = $user->profile_photo_path;
        $path = $request->file('photo')->store("profile-photos/{$user->id}", 'public');

        $user->forceFill(['profile_photo_path' => $path])->save();

        if ($oldPath && $oldPath !== $path) {
            Storage::disk('public')->delete($oldPath);
        }

        return to_route('profile.show')->with('profile_status', 'photo-updated');
    }

    public function destroyPhoto(Request $request): RedirectResponse
    {
        $user = $request->user();
        $path = $user->profile_photo_path;

        $user->forceFill(['profile_photo_path' => null])->save();

        if ($path) {
            Storage::disk('public')->delete($path);
        }

        return to_route('profile.show')->with('profile_status', 'photo-removed');
    }
}
