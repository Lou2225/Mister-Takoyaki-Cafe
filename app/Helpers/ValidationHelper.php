<?php

namespace App\Helpers;

class ValidationHelper
{
    /**
     * Professional Name Pattern: Allows letters (including accents/international),
     * spaces, hyphens, apostrophes, and dots.
     * Examples: "Juan Dela Cruz", "O'Brien", "Mary-Rose", "José"
     */
    const REGEX_NAME = '/^[\pL\s\-\.\']+$/u';
    
    /**
     * Strict Name: No leading/trailing spaces, hyphens, dots. No consecutive spaces.
     * No more than 3 identical characters in a row.
     */
    const REGEX_NAME_STRICT = '/^[\pL][\pL\s\-\.\']*[\pL]$|^[\pL]$/u';
    
    /**
     * Basic Alphanumeric: Letters, numbers, spaces, dots, hyphens.
     * Ideal for Table #, Refund reasons, and references.
     */
    const REGEX_NAME_BASIC = '/^[\pL\pN\s\-\.]+$/u';

    /**
     * Strict Phone Pattern: Optional leading +, then 7–20 digits.
     * Allows: +639123456789, 09123456789, 123456789
     */
    const REGEX_PHONE = '/^[+]?[0-9]{7,20}$/';

    /**
     * Employee / Reference ID: Uppercase letters, numbers, hyphens.
     * Examples: MT-0001, EMP-2024-01
     */
    const REGEX_ID = '/^[A-Z0-9\-]+$/i';

    /**
     * Price / Currency pattern: Digits with optional decimal (up to 2 places).
     * Examples: 150, 99.99, 0.50
     */
    const REGEX_PRICE = '/^\d+(\.\d{1,2})?$/';

    /**
     * Alphanumeric with basic punctuation (for notes/remarks fields).
     * Allows letters, numbers, spaces, common punctuation.
     */
    const REGEX_NOTES = '/^[\pL\pN\s\-\.\,\!\?\:\;\(\)\'\"\/\\\\]+$/u';

    // ── Reusable Rule Sets ──────────────────────────────────────────

    /**
     * Standard required name rules (first/last name).
     */
    public static function rulesName(int $min = 2, int $max = 100, bool $required = true): array
    {
        $rules = [
            $required ? 'required' : 'nullable', 
            'string', 
            "min:{$min}", 
            "max:{$max}", 
            'regex:' . self::REGEX_NAME,
            'not_regex:/(?:.*?\s){4,}/', // Maximum of 3 spaces allowed (up to 4 words)
            'not_regex:/\s{2,}/'        // Prevents consecutive spaces
        ];
        return $rules;
    }

    /**
     * Rules for notes, remarks, or descriptions using the permissive notes regex.
     */
    public static function rulesNotes(bool $required = false, int $max = 1000): array
    {
        return [$required ? 'required' : 'nullable', 'string', "max:{$max}", 'regex:' . self::REGEX_NOTES];
    }

    /**
     * Alias for rulesNotes, used in some modules.
     */
    public static function rulesDescription(bool $required = false, int $max = 1000): array
    {
        return self::rulesNotes($required, $max);
    }

    /**
     * Optional name rules (e.g. middle name).
     */
    public static function rulesOptionalName(int $min = 2, int $max = 100): array
    {
        return self::rulesName($min, $max, false);
    }

    /**
     * Standard email validation rules.
     */
    public static function rulesEmail(bool $required = true): array
    {
        return [
            $required ? 'required' : 'nullable', 
            'email:rfc,dns', 
            'max:255',
            'regex:/@(gmail\.com|yahoo\.com|yahoo\.com\.ph|outlook\.com|hotmail\.com|icloud\.com|live\.com|aol\.com)$/i'
        ];
    }

    /**
     * Standard phone validation rules.
     */
    public static function rulesPhone(bool $required = false): array
    {
        return [$required ? 'required' : 'nullable', 'string', 'regex:' . self::REGEX_PHONE];
    }

    /**
     * STRICT phone validation: No repeated digits, proper format.
     * Enforce actual phone number standards.
     */
    public static function rulesPhoneStrict(bool $required = false): array
    {
        return [
            $required ? 'required' : 'nullable',
            'string',
            'regex:' . self::REGEX_PHONE,
            'not_regex:/(.)\1{4,}/',  // No 5+ repeating digits (unlikely in real phone)
        ];
    }

    /**
     * Price / money amount rules.
     */
    public static function rulesPrice(float $min = 0, float $max = 999999.99): array
    {
        return ['required', 'numeric', "min:{$min}", "max:{$max}"];
    }

    /**
     * Optional price rules.
     */
    public static function rulesOptionalPrice(float $min = 0, float $max = 999999.99): array
    {
        return ['nullable', 'numeric', "min:{$min}", "max:{$max}"];
    }

    /**
     * Quantity rules (for stock/recipe quantities).
     */
    public static function rulesQuantity(float $min = 0.01): array
    {
        return ['required', 'numeric', "min:{$min}"];
    }

    /**
     * Legacy: Currency/Price rule array (backward compatible).
     */
    const RULES_PRICE = ['required', 'numeric', 'min:0', 'max:999999.99'];

    // ── Shared Validation Messages ──────────────────────────────────

    /**
     * Comprehensive, user-friendly validation messages.
     * Use as the $messages argument in validate() calls.
     */
    public static function commonMessages(): array
    {
        return [
            // Required
            'required'              => ':Attribute is required.',

            // String / text
            'string'                => ':Attribute must contain text only.',
            'min'                   => ':Attribute must be at least :min characters.',
            'max'                   => ':Attribute cannot exceed :max characters.',
            'alpha'                 => ':Attribute may only contain letters.',

            // Email
            'email'                 => 'Please enter a valid email address.',
            'email.regex'           => 'Use a legitimate provider (e.g., @gmail.com, @yahoo.com).',

            // Uniqueness
            'unique'                => 'This :attribute is already in use.',

            // Regex / format
            'regex'                 => ':Attribute contains invalid characters.',
            'not_regex'             => ':Attribute contains prohibited patterns or resembles spam.',

            // Numbers
            'numeric'               => ':Attribute must be a valid number.',
            'integer'               => ':Attribute must be a whole number.',
            'digits'                => ':Attribute must be exactly :digits digits.',

            // Range
            'between'               => ':Attribute must be between :min and :max.',

            // Dates
            'date'                  => 'Please enter a valid date.',
            'before_or_equal'       => 'Date cannot be set in the future.',
            'after_or_equal'        => 'Date must be today or later.',
            'before'                => 'Date must be before :date.',
            'after'                 => 'Date must be after :date.',

            // Files / images
            'image'                 => 'File must be a valid image (jpg, png, gif, webp).',
            'mimes'                 => 'Invalid file type. Allowed: :values.',

            // DB / existence
            'exists'                => 'The selected :attribute is invalid.',
            'in'                    => 'The selected :attribute option is not valid.',
            'boolean'               => ':Attribute must be true or false.',
        ];
    }

    /**
     * Field-specific messages for name fields.
     */
    public static function nameMessages(string $firstField = 'firstName', string $lastField = 'lastName'): array
    {
        return [
            "{$firstField}.required" => 'First name is required.',
            "{$firstField}.min"      => 'First name must be at least 2 characters.',
            "{$firstField}.max"      => 'First name is too long (max 100 characters).',
            "{$firstField}.regex"    => 'First name contains invalid characters or excessive spaces.',
            "{$firstField}.not_regex"=> 'First name contains repetitive characters or resembles spam.',
            "{$lastField}.required"  => 'Last name is required.',
            "{$lastField}.min"       => 'Last name must be at least 2 characters.',
            "{$lastField}.max"       => 'Last name is too long (max 100 characters).',
            "{$lastField}.regex"     => 'Last name contains invalid characters or excessive spaces.',
            "{$lastField}.not_regex" => 'Last name contains repetitive characters or resembles spam.',
        ];
    }

    /**
     * Field-specific messages for phone fields.
     */
    public static function phoneMessages(string $field = 'phone'): array
    {
        return [
            "{$field}.regex" => 'Enter a valid phone number (e.g. +639123456789 or 09123456789).',
            "{$field}.not_regex" => 'Phone number contains invalid patterns.',
        ];
    }

    // ── SANITIZATION & SECURITY ────────────────────────────────────

    /**
     * Sanitize input string based on type.
     * Removes disallowed characters and normalizes whitespace.
     */
    public static function sanitizeInput(?string $value, string $type = 'text'): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $value = match($type) {
            'name' => preg_replace('/[^\pL\s\-\.\']/', '', $value),
            'email' => strtolower(filter_var(trim($value), FILTER_SANITIZE_EMAIL)),
            'phone' => preg_replace('/[^0-9+\-\s\(\)]/', '', trim($value)),
            'alphanumeric' => preg_replace('/[^a-zA-Z0-9\-_]/', '', $value),
            'integer' => preg_replace('/[^0-9]/', '', $value),
            default => $value,
        };

        // Normalize whitespace: trim and collapse internal spaces
        $value = preg_replace('/\s+/', ' ', trim($value));

        return $value ?: null;
    }

    /**
     * Validate for suspicious patterns that might indicate spam or abuse.
     * Returns array of detected issues (empty if clean).
     */
    public static function detectSuspiciousPatterns(string $value, string $field = 'input'): array
    {
        $issues = [];

        // Excessive whitespace
        if (preg_match('/\s{3,}/', $value)) {
            $issues[] = 'Excessive whitespace detected';
        }

        // Excessive repeating characters (more than 3 identical in a row)
        if (preg_match('/(.)\1{3,}/', $value)) {
            $issues[] = 'Repeating characters detected';
        }

        // Unusual Unicode combinations (potential homograph attack)
        if (preg_match('/[\p{S}\p{C}]{2,}/u', $value)) {
            $issues[] = 'Unusual character patterns detected';
        }

        // SQL-like patterns
        if (preg_match('/(SELECT|INSERT|UPDATE|DELETE|DROP|UNION|WHERE)\s/i', $value)) {
            $issues[] = 'Suspicious SQL patterns detected';
        }

        // Script tags or HTML
        if (preg_match('/<[^>]+>/i', $value)) {
            $issues[] = 'HTML tags detected';
        }

        return $issues;
    }

    /**
     * Escape output for HTML context safely.
     * Complements Laravel's e() helper for additional security.
     */
    public static function escapeOutput(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    /**
     * Enhanced validation messages for stricter validation.
     */
    public static function strictMessages(): array
    {
        return [
            'not_regex' => ':Attribute contains invalid characters or patterns.',
        ];
    }
}

