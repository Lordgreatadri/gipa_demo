<x-auth-layout
    title="Create investor account"
    eyebrow="Investor registration"
    heading="Build your investment profile."
    description="Create a secure account to complete KYC onboarding, track review decisions and engage GIPA investor services."
>
    <h2>Create account</h2>
    <p>Enter your details to begin secure onboarding.</p>
    <form action="{{ route('register.store') }}" method="post">
        @csrf
        <label class="field"><span>Full name</span><input name="name" value="{{ old('name') }}" maxlength="255" required autofocus autocomplete="name">@error('name')<small class="field-error">{{ $message }}</small>@enderror</label>
        <label class="field"><span>Email address</span><input type="email" name="email" value="{{ old('email') }}" maxlength="255" required autocomplete="email">@error('email')<small class="field-error">{{ $message }}</small>@enderror</label>
        <label class="field"><span>Organization <small>(optional)</small></span><input name="organization" value="{{ old('organization') }}" maxlength="255" autocomplete="organization">@error('organization')<small class="field-error">{{ $message }}</small>@enderror</label>
        <label class="field"><span>Phone number <small>(optional)</small></span><input type="tel" name="phone" value="{{ old('phone') }}" maxlength="32" autocomplete="tel">@error('phone')<small class="field-error">{{ $message }}</small>@enderror</label>
        <x-password-field label="Password" autocomplete="new-password" />
        <x-password-field name="password_confirmation" label="Confirm password" autocomplete="new-password" />
        <label class="consent-field"><input type="checkbox" name="terms" value="1" required @checked(old('terms'))><span>I agree to the platform terms and acknowledge the privacy notice.</span></label>
        @error('terms')<small class="field-error">{{ $message }}</small>@enderror
        <button class="button button--gold" type="submit">Create secure account</button>
    </form>
    <a class="login-help" href="{{ route('login') }}">Already registered? Sign in</a>
</x-auth-layout>