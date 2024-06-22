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

// #BlockStart number=187 id=_19_0_3_40d01a2_1637682071471_424489_4969_#_0
// manual imports go here

// #BlockEnd number=187

enum AssessmentStatus: int
{
    case NEW = 0;
    case IN_EVALUATION = 1;
    case IN_VALIDATION = 2;
    case VALIDATED = 3;
    case IN_IMPROVEMENT = 4;
    case COMPLETE = 5;
    case ARCHIVED = 6;

    public function label(): string
    {
        return match ($this) {
            self::NEW => "NEW",
            self::IN_EVALUATION => "IN EVALUATION",
            self::IN_VALIDATION => "IN VALIDATION",
            self::VALIDATED => "VALIDATED",
            self::IN_IMPROVEMENT => "IN IMPROVEMENT",
            self::COMPLETE => "COMPLETE",
            self::ARCHIVED => "ARCHIVED",
        };
    }

    public static function fromLabel(string $label): self
    {
        $type = str_replace(" ", "_", $label);
        $value = array_search($type, array_column(self::cases(), "name", "value"), true);
        if ($value === false) {
            return self::NEW;
        }

        return self::from($value);
    }

// #BlockStart number=188 id=_19_0_3_40d01a2_1637682071471_424489_4969_#_1
// manual code goes here

// #BlockEnd number=188

}
