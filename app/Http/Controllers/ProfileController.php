<?php

namespace App\Http\Controllers;

use App\Http\Requests\PasswordUpdateRequest;
use App\Http\Requests\ProfileUpdateRequest;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Every action works on the signed-in user only. There is no user ID in these
 * routes, so one user cannot reach another user's profile by changing a URL.
 */
class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        return view('profile.edit', ['user' => $request->user()]);
    }

    public function update(ProfileUpdateRequest $request, ActivityLogger $activity): RedirectResponse
    {
        $user = $request->user();
        $user->update($request->validated());

        if ($user->wasChanged()) {
            $activity->log('profile.updated', 'Updated their profile', $user, $activity->changesOf($user));
        }

        return back()->with('success', 'Profile updated.');
    }

    public function updatePassword(PasswordUpdateRequest $request, ActivityLogger $activity): RedirectResponse
    {
        $user = $request->user();
        $user->update(['password' => $request->validated('password')]);

        $activity->log('profile.password_changed', 'Changed their password', $user);

        return back()->with('success', 'Password changed. Other devices have been signed out.');
    }
}
