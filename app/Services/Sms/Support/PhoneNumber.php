<?php

namespace App\Services\Sms\Support;

/**
 * Normalises raw user input into the shape each gateway accepts.
 *
 * Wigal Frog expects bare international digits (233201234567) while Twilio and
 * most other gateways expect E.164 (+233201234567).
 */
final class PhoneNumber
{
    private const MIN_NATIONAL_DIGITS = 7;

    public static function format(string $phone, ?string $countryCode = null, string $style = 'e164'): ?string
    {
        $countryCode = ltrim((string) ($countryCode ?: config('sms.default_country_code', '233')), '+');
        $digits = preg_replace('/\D/', '', $phone) ?? '';

        if ($digits === '' || preg_match('/^0+$/', $digits) === 1) {
            return null;
        }

        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        }

        if ($digits === '' || preg_match('/^0+$/', $digits) === 1) {
            return null;
        }

        if (! str_starts_with($digits, $countryCode)) {
            $digits = ltrim($digits, '0');

            if ($digits === '') {
                return null;
            }

            $digits = $countryCode.$digits;
        } elseif (preg_match('/^'.preg_quote($countryCode, '/').'0+$/', $digits) === 1) {
            return null;
        }

        // E.164 caps the whole number at 15 digits; the national part must still
        // be long enough to be a real subscriber number rather than a typo.
        if (strlen($digits) > 15 || strlen($digits) - strlen($countryCode) < self::MIN_NATIONAL_DIGITS) {
            return null;
        }

        return $style === 'digits' ? $digits : '+'.$digits;
    }

    public static function forProvider(string $phone, string $provider, ?string $countryCode = null): ?string
    {
        $style = (string) config('sms.providers.'.$provider.'.phone_format', 'e164');

        return self::format($phone, $countryCode, $style);
    }

    public static function isValid(string $phone, ?string $countryCode = null): bool
    {
        return self::format($phone, $countryCode) !== null;
    }

    /**
     * GSM-03.38 aware segment count used for credit estimation.
     */
    public static function segments(string $body): int
    {
        $length = mb_strlen($body);

        if ($length === 0) {
            return 1;
        }

        $unicode = ! mb_check_encoding($body, 'ASCII');
        $single = $unicode ? 70 : 160;
        $multi = $unicode ? 67 : 153;

        return $length <= $single ? 1 : (int) ceil($length / $multi);
    }
}
