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
        }
        $sanitizedValue = $sanitizer->sanitize($value);
        // The sanitizer is doing HTML encode, we will reverse the process on some symbols.
        return str_ireplace(array_values(self::ALLOWED_HTML_SYMBOLS), array_keys(self::ALLOWED_HTML_SYMBOLS), $sanitizedValue);
    }

    public function sanitizeEntityValue(?string $value, string $propertyName, AbstractEntity $entity): ?string
    {
        if ($value === null) {
            return null;
        }

        if (in_array($propertyName, $entity->getLessPurifiedFields(), true)) {
            return $this->sanitizeValue($value, self::LIBERAL);
        } else {
            return $this->sanitizeValue($value);
        }
    }
}
