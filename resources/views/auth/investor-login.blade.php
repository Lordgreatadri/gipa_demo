<x-auth-layout
    title="Investor sign in"
    eyebrow="Investor portal"
    heading="Continue your investment journey."
    description="Access saved opportunities, manage your profile and follow inquiry updates."
>
    <x-login-form
        :action="route('login.store')"
        :forgot-password-url="route('password.request', ['portal' => 'investor'])"
    />
</x-auth-layout>