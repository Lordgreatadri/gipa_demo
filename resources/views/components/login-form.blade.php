@props([
    'action',
    'forgotPasswordUrl',
])

<h2>Sign in</h2>
<p>Use your authorized account credentials.</p>
@if (session('status'))<div class="auth-status" role="status">{{ session('status') }}</div>@endif
<form action="{{ $action }}" method="post">
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
<a class="login-help" href="{{ $forgotPasswordUrl }}">Forgot your password?</a>