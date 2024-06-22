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

// #BlockStart number=195 id=_19_0_3_40d01a2_1646802191391_694690_4914_#_0
// manual imports go here

// #BlockEnd number=195

enum ValidationStatus: int
{
    case RETRACTED = -2;
    case REJECTED = -1;
    case NEW = 0;
    case AUTO_ACCEPTED = 1;
    case ACCEPTED = 2;

    public function label(): string
    {
        return match ($this) {
            self::RETRACTED => "RETRACTED",
            self::REJECTED => "REJECTED",
            self::NEW => "NEW",
            self::AUTO_ACCEPTED => "AUTO ACCEPTED",
            self::ACCEPTED => "ACCEPTED",
        };
    }

    public static function fromLabel(string $label): self
    {
        $type = str_replace(" ", "_", $label);
        $value = array_search($type, array_column(self::cases(), "name", "value"), true);
        if ($value === false) {
            return self::RETRACTED;
        }

        return self::from($value);
    }

// #BlockStart number=196 id=_19_0_3_40d01a2_1646802191391_694690_4914_#_1
// manual code goes here

// #BlockEnd number=196

}
