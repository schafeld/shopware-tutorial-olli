<?php declare(strict_types=1);

namespace Learning\Bundle\Service;

use Learning\Bundle\Exception\ValidationException;

class ValidationService
{
    /**
     * Validate a name string
     * 
     * @throws ValidationException
     */
    public function validateName(string $name): void
    {
        // Check if empty
        if (empty(trim($name))) {
            throw new ValidationException('Name cannot be empty');
        }

        // Check minimum length
        if (strlen($name) < 2) {
            throw new ValidationException('Name must be at least 2 characters long');
        }

        // Check maximum length
        if (strlen($name) > 50) {
            throw new ValidationException('Name must not exceed 50 characters');
        }

        // Check if contains only letters, spaces, and common name characters
        if (!preg_match('/^[a-zA-ZÀ-ÿ\s\'-]+$/u', $name)) {
            throw new ValidationException(
                'Name can only contain letters, spaces, hyphens, and apostrophes'
            );
        }
    }

    /**
     * Sanitize a name string
     */
    public function sanitizeName(string $name): string
    {
        // Trim whitespace
        $name = trim($name);

        // Remove multiple spaces
        $name = preg_replace('/\s+/', ' ', $name);

        // Capitalize first letter of each word
        $name = ucwords(strtolower($name));

        return $name;
    }

    /**
     * Validate and sanitize a name in one step
     * 
     * @throws ValidationException
     */
    public function processName(string $name): string
    {
        $sanitized = $this->sanitizeName($name);
        $this->validateName($sanitized);
        return $sanitized;
    }
}