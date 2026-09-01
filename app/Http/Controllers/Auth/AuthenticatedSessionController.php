<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function createInvestor(): View
    {
        return view('auth.investor-login');
    }

    public function createStaff(): View
    {
        return view('auth.staff-login');
    }

    public function storeInvestor(LoginRequest $request): RedirectResponse
    {
        $user = $request->authenticate(User::ACCOUNT_INVESTOR);
        $request->session()->regenerate();

        return redirect()->intended(route($user->hasVerifiedEmail() ? 'investor.dashboard' : 'verification.notice'));
    }

    public function storeStaff(LoginRequest $request): RedirectResponse
    {
        $user = $request->authenticate(User::ACCOUNT_STAFF);

        if (! $user->roles()->exists()) {
            Auth::logout();

            throw ValidationException::withMessages([
                'email' => __('This staff account has no assigned access role.'),
            ]);
        }

        $request->session()->regenerate();

        return redirect()->intended(route('staff.dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}