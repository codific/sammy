<?php

/**
 * This is automatically generated file using the Codific Prototizer
 * PHP version 8
 * @category PHP
 * @author   CODIFIC <info@codific.com>
 * @see     http://codific.com
 */

declare(strict_types=1);

namespace App\Enum;

// #BlockStart number=179 id=_19_0_3_40d01a2_1635866645740_816769_7117_#_0
// manual imports go here
use JetBrains\PhpStorm\Pure;

// #BlockEnd number=179

enum Role: int
{
    case USER = 0;
    case EVALUATOR = 2;
    case IMPROVER = 3;
    case VALIDATOR = 4;
    case MANAGER = 5;
    case AUDITOR = 6;
    case ADMINISTRATOR = 10;

    public function label(): string
    {
        return match ($this) {
            self::USER => "USER",
            self::EVALUATOR => "EVALUATOR",
            self::IMPROVER => "IMPROVER",
            self::VALIDATOR => "VALIDATOR",
            self::MANAGER => "MANAGER",
            self::AUDITOR => "AUDITOR",
            self::ADMINISTRATOR => "ADMINISTRATOR",
        };
    }

    public static function fromLabel(string $label): self
    {
        $type = str_replace(" ", "_", $label);
        $value = array_search($type, array_column(self::cases(), "name", "value"), true);
        if ($value === false) {
            return self::USER;
        }

        return self::from($value);
    }

// #BlockStart number=180 id=_19_0_3_40d01a2_1635866645740_816769_7117_#_1
    #[Pure]
    public function string(): string
    {
        return match ($this) {
            self::USER => "ROLE_USER",
            self::EVALUATOR => "ROLE_EVALUATOR",
            self::IMPROVER => "ROLE_IMPROVER",
            self::VALIDATOR => "ROLE_VALIDATOR",
            self::MANAGER => "ROLE_MANAGER",
            self::AUDITOR => "ROLE_AUDITOR",
            self::ADMINISTRATOR => "ROLE_ADMIN",
        };
    }

// #BlockEnd number=180

}
