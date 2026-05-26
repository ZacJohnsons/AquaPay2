<?php
// Copy this file to config.php and set your real API key.
define('API_KEY', 'your-api-key-here');

if (!function_exists('formatPhoneNumber')) {
    function formatPhoneNumber($phone): string
    {
        $phone = preg_replace('/\D/', '', $phone);

        if (substr($phone, 0, 1) === '0') {
            $phone = substr($phone, 1);
        } elseif (substr($phone, 0, 4) === '+256') {
            $phone = substr($phone, 4);
        } elseif (substr($phone, 0, 3) === '256') {
            $phone = substr($phone, 3);
        }

        $validPrefixes = ['76', '77', '78', '79', '31', '39'];

        if (strlen($phone) !== 9) {
            return 'Invalide phone number length';
        }

        $prefix = substr($phone, 0, 2);

        if (!in_array($prefix, $validPrefixes)) {
            return 'Invalid Mtn Number';
        }

        return '+256' . $phone;
    }
}
