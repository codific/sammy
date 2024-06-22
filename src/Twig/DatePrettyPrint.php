<?php

declare(strict_types=1);

namespace App\Twig;

use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class DatePrettyPrint extends AbstractExtension
{
    public function __construct(private readonly ?TranslatorInterface $translator)
    {
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('datePrettyPrint', $this->datePrettyPrint(...)),
            new TwigFilter('datePrettyPrintStrict', $this->datePrettyPrintStrict(...)),
            new TwigFilter('parseDateString', $this->parseDateString(...)),
        ];
    }

    public function datePrettyPrint(?\DateTime $dateTime, string $format = 'd-m-Y', ?string $defaultDate = null): string
    {
        if ($defaultDate === null) {
            $defaultDate = 'No date';
            if ($this->translator !== null) {
                $defaultDate = $this->translator->trans('admin.general.no_date');
            }
        }

        if ($dateTime !== null) {
            return $dateTime->format($format);
        }

        return $defaultDate;
    }

    public function datePrettyPrintStrict(?\DateTime $dateTime, string $format = 'd-m-Y'): string
    {
        if ($dateTime !== null) {
            return $dateTime->format($format);
        } elseif ($this->translator !== null) {
            return $this->translator->trans('admin.general.never');
        }

        return 'Never';
    }

    public function parseDateString(string $dateString): \DateTime
    {
        return new \DateTime($dateString);
    }
}
