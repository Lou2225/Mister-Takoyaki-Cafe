<?php

namespace App\Traits;

use Illuminate\Validation\ValidationException;
use App\Helpers\ValidationHelper;

trait HandlesValidations
{
    /**
     * Normalize string by trimming and collapsing internal multiple spaces.
     */
    protected function normalizeString(?string $value): string
    {
        if ($value === null || $value === '') return '';
        return preg_replace('/\s+/', ' ', trim($value));
    }

    /**
     * Sanitize input string based on type (name, email, phone, etc).
     * Wrapper around ValidationHelper::sanitizeInput for convenience.
     */
    protected function sanitize(?string $value, string $type = 'text'): ?string
    {
        return ValidationHelper::sanitizeInput($value, $type);
    }

    /**
     * Validate for suspicious patterns in the input.
     * Throws ValidationException if suspicious patterns are detected.
     */
    protected function validateSuspiciousPatterns(array $fields): void
    {
        $issues = [];

        foreach ($fields as $fieldName => $value) {
            if (is_string($value)) {
                $patterns = ValidationHelper::detectSuspiciousPatterns($value, $fieldName);
                if (!empty($patterns)) {
                    $issues[$fieldName] = $patterns[0]; // Report first issue
                }
            }
        }

        if (!empty($issues)) {
            throw ValidationException::withMessages($issues);
        }
    }

    /**
     * Validates data and automatically handles scroll-to-error and modal closing on failure.
     */
    protected function validateSecure(array $rules, array $messages = [], array $attributes = [], ?string $closeModalId = null): array
    {
        try {
            return $this->validate($rules, $messages, $attributes);
        } catch (ValidationException $e) {
            if ($closeModalId) {
                // Standardize: always dispatch as object for robust event handling
                $this->dispatchBrowserEvent('close-modal', ['name' => $closeModalId]);
            }
            $this->dispatchBrowserEvent('scroll-to-error');
            throw $e;
        }
    }

    /**
     * Real-time single-field validation for updated* lifecycle hooks.
     * Silently ignores if the field is empty and not required (avoid premature errors).
     *
     * Usage in component:
     *   public function updatedFirstName() { $this->validateFieldLive('firstName', ['required', 'string', 'min:2']); }
     */
    protected function validateFieldLive(string $field, array $rules, array $messages = []): void
    {
        try {
            $this->validateOnly($field, [$field => $rules], $messages);
        } catch (ValidationException $e) {
            // Validation errors are automatically added to the error bag by Livewire
            // We just need to let the exception propagate for Livewire to handle
            throw $e;
        }
    }

    /**
     * Pre-validate all fields before opening a confirmation modal.
     * If validation passes, fires the open-modal event.
     * If validation fails, fires scroll-to-error so user sees the errors.
     *
     * Usage:
     *   public function validateBeforeSave() {
     *       $this->validateBeforeModal([...rules...], [...messages...], 'confirm-save-modal');
     *   }
     */
    protected function validateBeforeModal(array $rules, array $messages, string $modalId, array $attributes = []): bool
    {
        try {
            if (!empty($rules)) {
                $this->validate($rules, $messages, $attributes);
            }
            // Standardize: always dispatch as object for robust event handling
            $this->dispatchBrowserEvent('open-modal', ['name' => $modalId]);
            return true;
        } catch (ValidationException $e) {
            $this->dispatchBrowserEvent('scroll-to-error');
            throw $e;
        }
    }

    /**
     * Common phone number cleansing (removes all non-digit/plus characters).
     */
    protected function cleanPhone(?string $phone): ?string
    {
        if (empty($phone)) return null;
        return preg_replace('/[^0-9+]/', '', $phone);
    }

    /**
     * Sanitize a price input — strips non-numeric chars except decimal point.
     */
    protected function cleanPrice(?string $price): ?string
    {
        if ($price === null || $price === '') return null;
        // Keep only digits and one decimal point
        $cleaned = preg_replace('/[^0-9.]/', '', (string) $price);
        // Prevent multiple decimal points
        $parts = explode('.', $cleaned);
        if (count($parts) > 2) {
            $cleaned = $parts[0] . '.' . implode('', array_slice($parts, 1));
        }
        return $cleaned;
    }

    /**
     * Safe output escaping for display.
     */
    protected function escape(?string $value): ?string
    {
        return ValidationHelper::escapeOutput($value);
    }
}
