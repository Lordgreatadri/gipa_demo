<?php

namespace Tests\Feature;

use Tests\TestCase;

class PasswordResetViewTest extends TestCase
{
    public function test_forgot_password_page_renders_the_recovery_form(): void
    {
        $this->get(route('password.request'))
            ->assertOk()
            ->assertSee('Forgot password?')
            ->assertSee(route('password.email'));
    }

    public function test_reset_password_page_renders_both_password_toggles(): void
    {
        $this->get(route('password.reset', ['token' => 'test-token', 'email' => 'user@example.test']))
            ->assertOk()
            ->assertSee('Choose a new password')
            ->assertSee('data-password-toggle', false)
            ->assertSee('password_confirmation');
    }

    public function test_login_page_renders_a_password_toggle(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('data-password-toggle', false)
            ->assertSee('Show password');
    }

    public function test_staff_login_page_renders_the_staff_form_and_password_toggle(): void
    {
        $this->get(route('staff.login'))
            ->assertOk()
            ->assertSee('Staff sign in')
            ->assertSee(route('staff.login.store'))
            ->assertSee('data-password-toggle', false);
    }
}
