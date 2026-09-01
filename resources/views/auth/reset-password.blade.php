<x-auth-layout
    title="Choose a new password"
    eyebrow="Account recovery"
    heading="Set a new password."
    description="Choose a strong password to restore secure access to your IOMP account."
>
    <h2>Choose a new password</h2>
    <p>Use at least 10 characters with upper and lower case letters and a number.</p>
    <form action="{{ route('password.update') }}" method="post">
        @csrf
        <input type="hidden" name="token" value="{{ $request->route('token') }}">
        <label class="field">
            <span>Email address</span>
            <input type="email" name="email" value="{{ old('email', $request->email) }}" required autofocus autocomplete="username">
            @error('email')<small class="field-error">{{ $message }}</small>@enderror
        </label>
        <x-password-field autocomplete="new-password" />
        <x-password-field name="password_confirmation" label="Confirm password" autocomplete="new-password" />
        <button class="button button--gold" type="submit">Update password</button>
    </form>
    <a class="login-help" href="{{ route($portal === 'staff' ? 'staff.login' : 'login') }}">Return to sign in</a>
</x-auth-layout>