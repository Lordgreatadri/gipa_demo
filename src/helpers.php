<?php

/**
 * TLMS global helpers.
 *
 * Loaded automatically via composer.json autoload.files.
 * Keep this file lean — only add functions used in multiple modules.
 */

use App\Models\Tenant;
use Src\Domain\Sms\SmsService;

if (! function_exists('sms_service_for_tenant')) {
    /**
     * Resolve a configured SmsService for the given tenant.
     *
     * Example:
     *   $sms = sms_service_for_tenant($tenant);
     */
    function sms_service_for_tenant(Tenant $tenant): SmsService
    {
        return SmsService::forTenant($tenant);
    }
}

if (! function_exists('system_sms_service')) {
    /**
     * Resolve the system-wide SMS orchestrator.
     */
    function system_sms_service(): SmsService
    {
        return SmsService::system();
    }
}

if (! function_exists('format_phone_for_sms')) {
    /**
     * Normalise a raw phone number for a specific SMS provider.
     *
    * Strips all non-digit characters, removes a leading zero when needed,
    * and returns either digits-only or E.164 style output depending on the
    * provider.
     *
     * Examples:
     *   format_phone_for_sms('0201234567', '233', 'frog')    -> '233201234567'
     *   format_phone_for_sms('+233201234567', '233', 'frog') -> '233201234567'
     *   format_phone_for_sms('0201234567', '233', 'twilio')  -> '+233201234567'
     */
    function format_phone_for_sms(string $phone, string $countryCode = '233', string $provider = 'twilio'): string
    {
        $digits = preg_replace('/\D/', '', $phone);

        if ($digits === '' || preg_match('/^0+$/', $digits) === 1) {
            return '';
        }

        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        }

        if ($digits === '' || preg_match('/^0+$/', $digits) === 1) {
            return '';
        }

        if (! str_starts_with($digits, $countryCode)) {
            $digits = ltrim($digits, '0');

            if ($digits === '') {
                return '';
            }

            $digits = $countryCode . $digits;
        } elseif (preg_match('/^'.preg_quote($countryCode, '/').'0+$/', $digits) === 1) {
            return '';
        }

        return $provider === 'frog' ? $digits : '+' . $digits;
    }
}
