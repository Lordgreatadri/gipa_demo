<x-auth-layout
    title="Staff sign in"
    eyebrow="Staff workspace"
    heading="Manage Ghana's investment pipeline."
    description="Authorized officers can review, approve and publish governed opportunity and district records."
>
    <x-login-form
        :action="route('staff.login.store')"
        :forgot-password-url="route('password.request', ['portal' => 'staff'])"
    />
</x-auth-layout>