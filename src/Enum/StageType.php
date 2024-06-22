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

// #BlockStart number=191 id=_19_0_3_40d01a2_1678546149669_932272_4856_#_0
// manual imports go here

// #BlockEnd number=191

enum StageType: int
{
    case EVALUATION = 1;
    case VALIDATION = 2;
    case IMPROVEMENT = 3;

    public function label(): string
    {
        return match ($this) {
            self::EVALUATION => "EVALUATION",
            self::VALIDATION => "VALIDATION",
            self::IMPROVEMENT => "IMPROVEMENT",
        };
    }

    public static function fromLabel(string $label): self
    {
        $type = str_replace(" ", "_", $label);
        $value = array_search($type, array_column(self::cases(), "name", "value"), true);
        if ($value === false) {
            return self::EVALUATION;
        }

        return self::from($value);
    }

// #BlockStart number=192 id=_19_0_3_40d01a2_1678546149669_932272_4856_#_1
// manual code goes here

// #BlockEnd number=192

}
