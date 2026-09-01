<x-auth-layout
    :title="$portal === 'staff' ? 'Staff sign in' : 'Investor sign in'"
    :eyebrow="$portal === 'staff' ? 'Staff workspace' : 'Investor portal'"
    :heading="$portal === 'staff' ? 'Manage Ghana’s investment pipeline.' : 'Continue your investment journey.'"
    :description="$portal === 'staff' ? 'Authorized officers can review, approve and publish governed opportunity and district records.' : 'Access saved opportunities and inquiry updates.'"
>
    <h2>Sign in</h2>
    <p>Use your authorized account credentials.</p>
    @if (session('status'))<div class="auth-status" role="status">{{ session('status') }}</div>@endif
    <form action="{{ $portal === 'staff' ? route('staff.login.store') : route('login.store') }}" method="post">
        @csrf
        <label class="field">
            <span>Email address</span>
            <input type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username">
            @error('email')<small class="field-error">{{ $message }}</small>@enderror
        </label>
        <x-password-field />
        <label class="consent-field"><input type="checkbox" name="remember" value="1"><span>Keep me signed in on this device</span></label>
        <button class="button button--gold" type="submit">Sign in securely</button>
    </form>
    <a class="login-help" href="{{ route('password.request') }}">Forgot your password?</a>
</x-auth-layout>