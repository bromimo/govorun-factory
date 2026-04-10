<?php

namespace App\Services;

/** Результат валидации схемы бота. */
class ValidationResult
{
    /** @param array<string> $errors */
    public function __construct(
        public array $errors = [],
    ) {}

    /** Валидна ли схема.
     */
    public function isValid(): bool
    {
        return empty($this->errors);
    }
}
