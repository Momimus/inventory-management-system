<?php

namespace App\Listeners;

use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\PasswordReset;

/**
 * Laravel discovers each handle* method and subscribes it to the event in its
 * type hint, so no manual registration is needed.
 */
class RecordAuthenticationActivity
{
    public function __construct(private ActivityLogger $activity) {}

    public function handleLogin(Login $event): void
    {
        $event->user->forceFill(['last_login_at' => now()])->saveQuietly();

        $this->activity->log('auth.login', 'Signed in', $event->user, user: $event->user);
    }

    public function handleLogout(Logout $event): void
    {
        if ($event->user instanceof User) {
            $this->activity->log('auth.logout', 'Signed out', $event->user, user: $event->user);
        }
    }

    public function handleFailed(Failed $event): void
    {
        // Only the attempted email is kept; the password never leaves the request.
        $this->activity->log(
            'auth.failed',
            'Failed sign-in attempt',
            $event->user,
            ['email' => $event->credentials['email'] ?? null],
            user: $event->user,
        );
    }

    public function handleLockout(Lockout $event): void
    {
        $this->activity->log(
            'auth.lockout',
            'Sign-in temporarily blocked after repeated failures',
            properties: ['email' => $event->request->input('email')],
        );
    }

    public function handlePasswordReset(PasswordReset $event): void
    {
        $this->activity->log('auth.password_reset', 'Reset their password by email link', $event->user, user: $event->user);
    }
}
