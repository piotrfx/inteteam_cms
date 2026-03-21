<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Inertia\Inertia;
use Inertia\Response;

final class ForgotPasswordController extends Controller
{
    public function showLinkRequestForm(): Response
    {
        return Inertia::render('Admin/Auth/ForgotPassword', [
            'status' => session('status'),
        ]);
    }

    public function sendResetLinkEmail(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        Password::broker('cms_users')->sendResetLink(
            $request->only('email'),
        );

        // Always return the same response to prevent user enumeration
        return back()->with('status', __('passwords.sent'));
    }
}
