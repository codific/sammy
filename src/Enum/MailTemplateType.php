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

// #BlockStart number=181 id=_19_0_3_40d01a2_1637589865228_863475_5033_#_0
// manual imports go here

// #BlockEnd number=181

enum MailTemplateType: int
{
    case NOTIFICATION = 0;
    case ADMIN_PASSWORD_RESET = 1;
    case USER_PASSWORD_RESET = 2;
    case USER_WELCOME = 3;
    case CHANGED_PASSWORD = 4;
    case USER_WELCOME_SSO = 50;

    public function label(): string
    {
        return match ($this) {
            self::NOTIFICATION => "NOTIFICATION",
            self::ADMIN_PASSWORD_RESET => "ADMIN PASSWORD RESET",
            self::USER_PASSWORD_RESET => "USER PASSWORD RESET",
            self::USER_WELCOME => "USER WELCOME",
            self::CHANGED_PASSWORD => "CHANGED PASSWORD",
            self::USER_WELCOME_SSO => "USER WELCOME SSO",
        };
    }

    public static function fromLabel(string $label): self
    {
        $type = str_replace(" ", "_", $label);
        $value = array_search($type, array_column(self::cases(), "name", "value"), true);
        if ($value === false) {
            return self::NOTIFICATION;
        }

        return self::from($value);
    }

// #BlockStart number=182 id=_19_0_3_40d01a2_1637589865228_863475_5033_#_1
// manual code goes here

// #BlockEnd number=182

}
