<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Abstraction\AbstractEntity;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerInterface;

class SanitizerService
{
    public const STRICT = 1;
    public const LIBERAL = 2;

    private const ALLOWED_HTML_SYMBOLS = [
        '+' => '&#43;',
        '=' => '&#61;',
        '@' => '&#64;',
        '>' => '&gt;',
        '<' => '&lt;',
        '&' => '&amp;',
        '\'' => '&#039;',
        '"' => '&#34;',
        '`' => '&#96;',
    ];

    public function __construct(
        private readonly HtmlSanitizerInterface $strictSanitizer,
        private readonly HtmlSanitizerInterface $liberalSanitizer
    ) {
    }

    public function sanitizeValue(?string $value, int $sanitizeType = self::STRICT): ?string
    {
        if ($value === null) {
            return null;
        }

        $sanitizer = $this->strictSanitizer;
        if ($sanitizeType === self::LIBERAL) {
            $sanitizer = $this->liberalSanitizer;

            return $sanitizer->sanitize($value);
        } else {
            // Sanitizer is removing < symbol We will allow it only if it is followed by a space
            $value = str_replace('< ', '&lt; ', $value);

            $sanitizedValue = $sanitizer->sanitize($value);

            // The sanitizer is doing HTML encode, we will reverse the process on some symbols.
            return str_ireplace(array_values(self::ALLOWED_HTML_SYMBOLS), array_keys(self::ALLOWED_HTML_SYMBOLS), $sanitizedValue);
        }
    }

    public function sanitizeEntityValue(?string $value, string $propertyName, AbstractEntity $entity): ?string
    {
        if ($value === null) {
            return null;
        }

        if (in_array($propertyName, $entity->getLessPurifiedFields(), true)) {
            return $this->sanitizeValue($value, self::LIBERAL);
        }

        return $this->sanitizeValue($value, self::STRICT);
    }

    public function sanitizeWordValue(string|null $value, string $word, bool $stripHtmlChars = true): string
    {
        if ($value === null || strlen($value) < 1) {
            return '';
        }

        $sanitizedValue = str_ireplace($word, '', $value);

        if ($stripHtmlChars) {
            // strips html tags
            $sanitizedValue = htmlspecialchars($sanitizedValue, ENT_QUOTES | ENT_HTML5, 'UTF-8', true);
        }

        return $sanitizedValue;
    }

    public function sanitizeHtmlChars(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8', true);
    }
}
