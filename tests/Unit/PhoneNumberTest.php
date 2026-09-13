<?php

namespace Tests\Unit;

use App\Services\Sms\Support\PhoneNumber;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PhoneNumberTest extends TestCase
{
    /**
     * @return array<string, array{0: string, 1: ?string}>
     */
    public static function e164Cases(): array
    {
        return [
            'local with leading zero' => ['0241234567', '+233241234567'],
            'spaced local' => ['024 123 4567', '+233241234567'],
            'dashed local' => ['024-123-4567', '+233241234567'],
            'already international' => ['+233241234567', '+233241234567'],
            'international without plus' => ['233241234567', '+233241234567'],
            'double zero prefix' => ['00233241234567', '+233241234567'],
            'bracketed' => ['(024) 123 4567', '+233241234567'],
            'empty' => ['', null],
            'all zeroes' => ['0000000000', null],
            'too short' => ['12345', null],
            'letters only' => ['not a number', null],
        ];
    }

    #[DataProvider('e164Cases')]
    public function test_numbers_are_normalised_to_e164(string $input, ?string $expected): void
    {
        $this->assertSame($expected, PhoneNumber::format($input));
    }

    public function test_frog_receives_bare_digits_and_twilio_receives_e164(): void
    {
        $this->assertSame('233241234567', PhoneNumber::forProvider('0241234567', 'frog'));
        $this->assertSame('+233241234567', PhoneNumber::forProvider('0241234567', 'twilio'));
    }

    public function test_a_different_country_code_can_be_supplied(): void
    {
        $this->assertSame('+2348012345678', PhoneNumber::format('08012345678', '234'));
        $this->assertSame('+442071234567', PhoneNumber::format('02071234567', '+44'));
    }

    public function test_segment_counting_follows_gsm_and_unicode_limits(): void
    {
        $this->assertSame(1, PhoneNumber::segments(''));
        $this->assertSame(1, PhoneNumber::segments(str_repeat('a', 160)));
        $this->assertSame(2, PhoneNumber::segments(str_repeat('a', 161)));
        $this->assertSame(2, PhoneNumber::segments(str_repeat('a', 306)));
        $this->assertSame(3, PhoneNumber::segments(str_repeat('a', 307)));

        // Unicode content drops the per-segment allowance to 70/67 characters.
        $this->assertSame(1, PhoneNumber::segments(str_repeat('é', 70)));
        $this->assertSame(2, PhoneNumber::segments(str_repeat('é', 71)));
    }

    public function test_validity_check_matches_normalisation(): void
    {
        $this->assertTrue(PhoneNumber::isValid('0241234567'));
        $this->assertFalse(PhoneNumber::isValid('123'));
        $this->assertFalse(PhoneNumber::isValid('0'));
    }
}
