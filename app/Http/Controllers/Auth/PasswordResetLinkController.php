<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;

class PasswordResetLinkController extends Controller
{
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    /**
     * The response is the same whether or not the email belongs to an account,
     * so the form cannot be used to discover registered addresses.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate(['email' => ['required', 'string', 'email', 'max:255']]);

        Password::sendResetLink([
            'email' => $request->string('email')->toString(),
            'is_active' => true,
        ]);

        return back()->with('success', 'If that address belongs to an active account, a reset link has been sent to it.');
    }
}
