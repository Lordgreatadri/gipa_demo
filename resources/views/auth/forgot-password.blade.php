<x-auth-layout
    title="Reset password"
    eyebrow="Account recovery"
    heading="Recover secure access."
    description="Request a time-limited reset link for your registered investor or staff account."
>
    <h2>Forgot password?</h2>
    <p>Enter your registered email address. For privacy, we show the same response whether or not an account exists.</p>
    @if (session('status'))<div class="auth-status" role="status">{{ session('status') }}</div>@endif
    <form action="{{ route('password.email') }}" method="post">
        @csrf
        <label class="field">
            <span>Email address</span>
            <input type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="email">
            @error('email')<small class="field-error">{{ $message }}</small>@enderror
        </label>
        <button class="button button--gold" type="submit">Send reset link</button>
    </form>
    <a class="login-help" href="{{ route($portal === 'staff' ? 'staff.login' : 'login') }}">Return to sign in</a>
</x-auth-layout>