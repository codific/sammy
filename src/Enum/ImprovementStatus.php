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

// #BlockStart number=197 id=_19_0_3_40d01a2_1646821426970_156357_4850_#_0
// manual imports go here

// #BlockEnd number=197

enum ImprovementStatus: int
{
    case WONT_IMPROVE = -1;
    case NEW = 0;
    case DRAFT = 1;
    case IMPROVE = 2;

    public function label(): string
    {
        return match ($this) {
            self::WONT_IMPROVE => "WONT IMPROVE",
            self::NEW => "NEW",
            self::DRAFT => "DRAFT",
            self::IMPROVE => "IMPROVE",
        };
    }

    public static function fromLabel(string $label): self
    {
        $type = str_replace(" ", "_", $label);
        $value = array_search($type, array_column(self::cases(), "name", "value"), true);
        if ($value === false) {
            return self::WONT_IMPROVE;
        }

        return self::from($value);
    }

// #BlockStart number=198 id=_19_0_3_40d01a2_1646821426970_156357_4850_#_1
// manual code goes here

// #BlockEnd number=198

}
