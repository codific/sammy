<?php

declare(strict_types=1);

namespace App\Enum\Custom;


enum RemarkType: int
{
    case DOCUMENTATION = 0;
    case VALIDATION = 1;
    case IMPROVEMENT = 2;

    public function label(): string
    {
        return match ($this) {
            self::DOCUMENTATION => 'DOCUMENTATION',
            self::VALIDATION => 'VALIDATION',
            self::IMPROVEMENT => 'IMPROVEMENT',
        };
    }

    public static function fromLabel(string $label): self
    {
        $type = str_replace(' ', '_', $label);
        $value = array_search($type, array_column(self::cases(), 'name', 'value'), true);
        if ($value === false) {
            return self::DOCUMENTATION;
        }

        return self::from($value);
    }

}
