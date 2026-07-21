<?php

namespace App\Http\Controllers;

use App\Http\Requests\ConfirmProfileTwoFactorRequest;
use App\Http\Requests\DisableProfileTwoFactorRequest;
use App\Http\Requests\EnableProfileTwoFactorRequest;
use App\Http\Requests\ProfileRecoveryCodesRequest;
use Illuminate\Http\RedirectResponse;
use Laravel\Fortify\Actions\ConfirmTwoFactorAuthentication;
use Laravel\Fortify\Actions\DisableTwoFactorAuthentication;
use Laravel\Fortify\Actions\EnableTwoFactorAuthentication;
use Laravel\Fortify\Actions\GenerateNewRecoveryCodes;

class ProfileTwoFactorController extends Controller
{
    public function enable(
        EnableProfileTwoFactorRequest $request,
        EnableTwoFactorAuthentication $enable,
        DisableTwoFactorAuthentication $disable,
    ): RedirectResponse {
        $disable($request->user());
        $enable($request->user(), true);

        return to_route('profile.show')->with('profile_status', 'two-factor-setup');
    }

    public function confirm(ConfirmProfileTwoFactorRequest $request, ConfirmTwoFactorAuthentication $confirm): RedirectResponse
    {
        $confirm($request->user(), $request->validated('code'));

        return to_route('profile.show')->with([
            'profile_status' => 'two-factor-enabled',
            'recovery_codes' => $request->user()->fresh()->recoveryCodes(),
        ]);
    }

    public function disable(DisableProfileTwoFactorRequest $request, DisableTwoFactorAuthentication $disable): RedirectResponse
    {
        $disable($request->user());

        return to_route('profile.show')->with('profile_status', 'two-factor-disabled');
    }

    public function showRecoveryCodes(ProfileRecoveryCodesRequest $request): RedirectResponse
    {
        abort_unless($request->user()->hasEnabledTwoFactorAuthentication(), 404);

        return to_route('profile.show')->with('recovery_codes', $request->user()->recoveryCodes());
    }

    public function regenerateRecoveryCodes(
        ProfileRecoveryCodesRequest $request,
        GenerateNewRecoveryCodes $generate,
    ): RedirectResponse {
        abort_unless($request->user()->hasEnabledTwoFactorAuthentication(), 404);
        $generate($request->user());

        return to_route('profile.show')->with([
            'profile_status' => 'recovery-codes-regenerated',
            'recovery_codes' => $request->user()->fresh()->recoveryCodes(),
        ]);
    }
}
