<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class PhilippinePhoneNumber implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @param  Closure  $fail
     * @return void
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!$this->isValidPhilippinePhoneNumber($value)) {
            $fail('The ' . $attribute . ' must be a valid Philippine phone number (e.g., +63912345678 or 09123456789).');
        }
    }

    /**
     * Validate Philippine phone number format
     * Accepts: +63 format or 09 format
     */
    private function isValidPhilippinePhoneNumber(string $phone): bool
    {
        // Remove spaces and hyphens
        $phone = preg_replace('/[\s\-]/', '', $phone);

        // Check for +63 format (international)
        if (preg_match('/^\+63\d{9,10}$/', $phone)) {
            return true;
        }

        // Check for 09 format (local)
        if (preg_match('/^09\d{9}$/', $phone)) {
            return true;
        }

        return false;
    }

    /**
     * Normalize Philippine phone number to +63 format
     */
    public static function normalize(string $phone): string
    {
        $phone = preg_replace('/[\s\-]/', '', $phone);

        // Convert 09 format to +63 format
        if (preg_match('/^09(\d{9})$/', $phone, $matches)) {
            return '+63' . $matches[1];
        }

        return $phone;
    }
}
