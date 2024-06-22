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

// #BlockStart number=183 id=_19_0_3_40d01a2_1637590004255_97200_5063_#_0
// manual imports go here

// #BlockEnd number=183

enum MailingStatus: int
{
    case FAILED = -1;
    case NEW = 0;
    case PROCESSING = 1;
    case SENT = 2;

    public function label(): string
    {
        return match ($this) {
            self::FAILED => "FAILED",
            self::NEW => "NEW",
            self::PROCESSING => "PROCESSING",
            self::SENT => "SENT",
        };
    }

    public static function fromLabel(string $label): self
    {
        $type = str_replace(" ", "_", $label);
        $value = array_search($type, array_column(self::cases(), "name", "value"), true);
        if ($value === false) {
            return self::FAILED;
        }

        return self::from($value);
    }

// #BlockStart number=184 id=_19_0_3_40d01a2_1637590004255_97200_5063_#_1
// manual code goes here

// #BlockEnd number=184

}
