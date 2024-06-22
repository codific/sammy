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

// #BlockStart number=199 id=_19_0_3_40d01a2_1649058946458_305879_5075_#_0
// manual imports go here

// #BlockEnd number=199

enum AssessmentAnswerType: int
{
    case CURRENT = 0;
    case DESIRED = 1;

    public function label(): string
    {
        return match ($this) {
            self::CURRENT => "CURRENT",
            self::DESIRED => "DESIRED",
        };
    }

    public static function fromLabel(string $label): self
    {
        $type = str_replace(" ", "_", $label);
        $value = array_search($type, array_column(self::cases(), "name", "value"), true);
        if ($value === false) {
            return self::CURRENT;
        }

        return self::from($value);
    }

// #BlockStart number=200 id=_19_0_3_40d01a2_1649058946458_305879_5075_#_1
// manual code goes here

// #BlockEnd number=200

}
