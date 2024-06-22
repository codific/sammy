<?php

declare(strict_types=1);

namespace App\Utils;

use App\Entity\User;
use Symfony\Component\Security\Core\User\UserInterface;

class DateTimeUtil
{
    public const PREPEND_SCHEMA_TIME_THEN_DATE = 1;
    public const PREPEND_SCHEMA_DATE_THEN_TIME = 2;
    public const DEFAULT_DATE_FORMAT = 'd-m-Y';

    /**
     * @return string[]
     */
    public function getAvailableDateFormats(): array
    {
        return [
            'd-m-Y' => 'DD-MM-YYYY',
            'm-d-Y' => 'MM-DD-YYYY',
            'Y-m-d' => 'YYYY-MM-DD',
        ];
    }

    /**
     * @return string[]
     */
    public function getAvailableTimeZones(): array
    {
        return [
            'Etc/GMT+12' => '(UTC-12:00) International Date Line West',
            'Etc/GMT+11' => '(UTC-11:00) Coordinated Universal Time-11',
            'Pacific/Honolulu' => '(UTC-10:00) Hawaii',
            'America/Anchorage' => '(UTC-09:00) Alaska',
            'America/Santa_Isabel' => '(UTC-08:00) Baja California',
            'America/Los_Angeles' => '(UTC-08:00) Pacific Time (US and Canada)',
            'America/Chihuahua' => '(UTC-07:00) Chihuahua, La Paz, Mazatlan',
            'America/Phoenix' => '(UTC-07:00) Arizona',
            'America/Denver' => '(UTC-07:00) Mountain Time (US and Canada)',
            'America/Guatemala' => '(UTC-06:00) Central America',
            'America/Chicago' => '(UTC-06:00) Central Time (US and Canada)',
            'America/Regina' => '(UTC-06:00) Saskatchewan',
            'America/Mexico_City' => '(UTC-06:00) Guadalajara, Mexico City, Monterey',
            'America/Bogota' => '(UTC-05:00) Bogota, Lima, Quito',
            'America/Indiana/Indianapolis' => '(UTC-05:00) Indiana (East)',
            'America/New_York' => '(UTC-05:00) Eastern Time (US and Canada)',
            'America/Caracas' => '(UTC-04:30) Caracas',
            'America/Halifax' => '(UTC-04:00) Atlantic Time (Canada)',
            'America/Asuncion' => '(UTC-04:00) Asuncion',
            'America/La_Paz' => '(UTC-04:00) Georgetown, La Paz, Manaus, San Juan',
            'America/Cuiaba' => '(UTC-04:00) Cuiaba',
            'America/Santiago' => '(UTC-04:00) Santiago',
            'America/St_Johns' => '(UTC-03:30) Newfoundland',
            'America/Sao_Paulo' => '(UTC-03:00) Brasilia',
            'America/Godthab' => '(UTC-03:00) Greenland',
            'America/Cayenne' => '(UTC-03:00) Cayenne, Fortaleza',
            'America/Argentina/Buenos_Aires' => '(UTC-03:00) Buenos Aires',
            'America/Montevideo' => '(UTC-03:00) Montevideo',
            'Etc/GMT+2' => '(UTC-02:00) Coordinated Universal Time-2',
            'Atlantic/Cape_Verde' => '(UTC-01:00) Cape Verde',
            'Atlantic/Azores' => '(UTC-01:00) Azores',
            'Africa/Casablanca' => '(UTC+00:00) Casablanca',
            'Atlantic/Reykjavik' => '(UTC+00:00) Monrovia, Reykjavik',
            'Europe/London' => '(UTC+00:00) Dublin, Edinburgh, Lisbon, London',
            'Etc/GMT' => '(UTC+00:00) Coordinated Universal Time',
            'Europe/Berlin' => '(UTC+01:00) Amsterdam, Berlin, Bern, Rome, Stockholm, Vienna',
            'Europe/Paris' => '(UTC+01:00) Brussels, Copenhagen, Madrid, Paris',
            'Africa/Lagos' => '(UTC+01:00) West Central Africa',
            'Europe/Budapest' => '(UTC+01:00) Belgrade, Bratislava, Budapest, Ljubljana, Prague',
            'Europe/Warsaw' => '(UTC+01:00) Sarajevo, Skopje, Warsaw, Zagreb',
            'Africa/Windhoek' => '(UTC+01:00) Windhoek',
            'Europe/Istanbul' => '(UTC+02:00) Athens, Bucharest, Istanbul',
            'Europe/Kiev' => '(UTC+02:00) Helsinki, Kiev, Riga, Sofia, Tallinn, Vilnius',
            'Africa/Cairo' => '(UTC+02:00) Cairo',
            'Asia/Damascus' => '(UTC+02:00) Damascus',
            'Asia/Amman' => '(UTC+02:00) Amman',
            'Africa/Johannesburg' => '(UTC+02:00) Harare, Pretoria',
            'Asia/Jerusalem' => '(UTC+02:00) Jerusalem',
            'Asia/Beirut' => '(UTC+02:00) Beirut',
            'Asia/Baghdad' => '(UTC+03:00) Baghdad',
            'Europe/Minsk' => '(UTC+03:00) Minsk',
            'Asia/Riyadh' => '(UTC+03:00) Kuwait, Riyadh',
            'Africa/Nairobi' => '(UTC+03:00) Nairobi',
            'Asia/Tehran' => '(UTC+03:30) Tehran',
            'Europe/Moscow' => '(UTC+04:00) Moscow, St. Petersburg, Volgograd',
            'Asia/Tbilisi' => '(UTC+04:00) Tbilisi',
            'Asia/Yerevan' => '(UTC+04:00) Yerevan',
            'Asia/Dubai' => '(UTC+04:00) Abu Dhabi, Muscat',
            'Asia/Baku' => '(UTC+04:00) Baku',
            'Indian/Mauritius' => '(UTC+04:00) Port Louis',
            'Asia/Kabul' => '(UTC+04:30) Kabul',
            'Asia/Tashkent' => '(UTC+05:00) Tashkent',
            'Asia/Karachi' => '(UTC+05:00) Islamabad, Karachi',
            'Asia/Colombo' => '(UTC+05:30) Sri Jayewardenepura Kotte',
            'Asia/Kolkata' => '(UTC+05:30) Chennai, Kolkata, Mumbai, New Delhi',
            'Asia/Kathmandu' => '(UTC+05:45) Kathmandu',
            'Asia/Almaty' => '(UTC+06:00) Astana',
            'Asia/Dhaka' => '(UTC+06:00) Dhaka',
            'Asia/Yekaterinburg' => '(UTC+06:00) Yekaterinburg',
            'Asia/Yangon' => '(UTC+06:30) Yangon',
            'Asia/Bangkok' => '(UTC+07:00) Bangkok, Hanoi, Jakarta',
            'Asia/Novosibirsk' => '(UTC+07:00) Novosibirsk',
            'Asia/Krasnoyarsk' => '(UTC+08:00) Krasnoyarsk',
            'Asia/Ulaanbaatar' => '(UTC+08:00) Ulaanbaatar',
            'Asia/Shanghai' => '(UTC+08:00) Beijing, Chongqing, Hong Kong, Urumqi',
            'Australia/Perth' => '(UTC+08:00) Perth',
            'Asia/Singapore' => '(UTC+08:00) Kuala Lumpur, Singapore',
            'Asia/Taipei' => '(UTC+08:00) Taipei',
            'Asia/Irkutsk' => '(UTC+09:00) Irkutsk',
            'Asia/Seoul' => '(UTC+09:00) Seoul',
            'Asia/Tokyo' => '(UTC+09:00) Osaka, Sapporo, Tokyo',
            'Australia/Darwin' => '(UTC+09:30) Darwin',
            'Australia/Adelaide' => '(UTC+09:30) Adelaide',
            'Australia/Hobart' => '(UTC+10:00) Hobart',
            'Asia/Yakutsk' => '(UTC+10:00) Yakutsk',
            'Australia/Brisbane' => '(UTC+10:00) Brisbane',
            'Pacific/Port_Moresby' => '(UTC+10:00) Guam, Port Moresby',
            'Australia/Sydney' => '(UTC+10:00) Canberra, Melbourne, Sydney',
            'Asia/Vladivostok' => '(UTC+11:00) Vladivostok',
            'Pacific/Guadalcanal' => '(UTC+11:00) Solomon Islands, New Caledonia',
            'Etc/GMT-12' => '(UTC+12:00) Coordinated Universal Time+12',
            'Pacific/Fiji' => '(UTC+12:00) Fiji, Marshall Islands',
            'Asia/Magadan' => '(UTC+12:00) Magadan',
            'Pacific/Auckland' => '(UTC+12:00) Auckland, Wellington',
            'Pacific/Tongatapu' => '(UTC+13:00) Nuku\'alofa',
            'Pacific/Apia' => '(UTC+13:00) Samoa',
        ];
    }

    public function convertDateTimeToUserSettings(\DateTime $dateTime, UserInterface $user, string $timeFormat = 'H:i:s', int $prependSchema = self::PREPEND_SCHEMA_TIME_THEN_DATE): string
    {
        if ($user instanceof User) {
            $userDateTime = $this->convertTimeToUserSettings($dateTime, $user);
            $newDateFormat = $user->getDateFormat() ?? 'd-m-Y';
        } else {
            $newDateFormat = 'd-m-Y';
            $userDateTime = $dateTime;
        }

        if ($prependSchema === self::PREPEND_SCHEMA_DATE_THEN_TIME) {
            return $userDateTime->format("{$newDateFormat} {$timeFormat}");
        }

        return $userDateTime->format("{$timeFormat} {$newDateFormat}");
    }

    /**
     * e.g. "Europe/Sofia" => UTC+2:00.
     */
    public function getStandardOffsetUTC($timezone): string
    {
        if ($timezone === 'UTC') {
            return 'UTC+00:00';
        }
        $timezone = new \DateTimeZone($timezone);
        $transitions = $timezone->getTransitions(time() - 86400 * 365 * 2, time());
        foreach ($transitions as $transition) {
            if (!$transition['isdst']) {
                return sprintf('UTC%+03d:%02u', $transition['offset'] / 3600, abs($transition['offset']) % 3600 / 60);
            }
        }

        return '';
    }

    private function convertTimeToUserSettings(\DateTime $dateTime, User $user): \DateTime
    {
        $dateTimeClone = clone $dateTime;
        $dateTimeClone->setTimezone($this->getTimeZone($user));

        return $dateTimeClone;
    }

    private function getTimeZone(User $user): \DateTimeZone
    {
        $timeZone = $user->getTimeZone() === null || $user->getTimeZone() === '' ? date_default_timezone_get() : $user->getTimeZone();

        return new \DateTimeZone($timeZone);
    }

    public function getCurrentQuarterStartDate(): \DateTime
    {
        $date = new \DateTime('now');
        $currentMonth = (int) $date->format('m');
        $currentYear = (int) $date->format('Y');
        $currentQuarterNr = (int) ceil($currentMonth / 3);
        $currentQuarterStartMonth = $currentQuarterNr * 3 - 2;
        $date->setDate($currentYear, $currentQuarterStartMonth, 1);

        return $date;
    }

    public function getRelativeQuarterStartDate(int $offset = 0): \DateTime
    {
        $currentQStartDate = $this->getCurrentQuarterStartDate();

        return match ($offset <=> 0) {
            1 => $currentQStartDate->add(new \DateInterval('P'.$offset * 3 .'M')),
            -1 => $currentQStartDate->sub(new \DateInterval('P'.abs($offset) * 3 .'M')),
            0 => $currentQStartDate
        };
    }

    public function getRelativeQuarterEndDate(int $offset = 0): \DateTime
    {
        $relativeQStartDate = $this->getRelativeQuarterStartDate($offset);

        $relativeQStartDate->add(new \DateInterval('P3M'));
        $relativeQStartDate->sub(new \DateInterval('P1D'));

        return $relativeQStartDate;
    }
}
