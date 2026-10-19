<x-auth-layout
    title="Verify your email"
    eyebrow="Email verification"
    heading="Confirm your email address."
    description="Verify your email address to activate secure access to your GIPA investor account and continue your onboarding."

>

<h2>Check your inbox</h2>

<p>
    We’ve sent a verification link to your registered email address.
    Please open the email and click the verification link to continue.
</p>

@if (session('status'))
    <div class="auth-status" role="status">
        {{ session('status') }}
    </div>
@endif

<div class="verification-panel">
    <p>
        Didn’t receive the email? Check your spam or junk folder, or request
        a new verification link below.
    </p>

    <form action="{{ route('verification.send') }}" method="post">
        @csrf
        <button class="button button--gold" type="submit">
            Resend verification email
        </button>
    </form>
</div>

<form action="{{ route('logout') }}" method="post" class="logout-form">
    @csrf
    <button type="submit" class="login-help">
        Sign out
    </button>
</form>
</x-auth-layout>