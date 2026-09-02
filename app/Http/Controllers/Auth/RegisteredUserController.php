<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterInvestorRequest;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class RegisteredUserController extends Controller
{
    public function create(): View
    {
        return view('auth.register');
    }

    public function store(RegisterInvestorRequest $request): RedirectResponse
    {
        $user = DB::transaction(function () use ($request): User {
            $user = User::create([
                ...$request->safe()->only(['name', 'email', 'organization', 'phone', 'password']),
                'account_type' => User::ACCOUNT_INVESTOR,
                'status' => User::STATUS_ACTIVE,
            ]);

            $user->assignRole(Role::findOrCreate('Investor', 'web'));
            $user->investorProfile()->create([
                'display_name' => $user->name,
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ]);

            return $user;
        });

        event(new Registered($user));
        Auth::login($user);

        return redirect()->route('verification.notice');
    }
}