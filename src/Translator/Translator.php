<?php

declare(strict_types=1);

namespace App\Translator;

use JetBrains\PhpStorm\Pure;
use Symfony\Component\Translation\MessageCatalogueInterface;
use Symfony\Component\Translation\Translator as BaseTranslator;
use Symfony\Component\Translation\TranslatorBagInterface;
use Symfony\Contracts\Translation\LocaleAwareInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class Translator implements TranslatorInterface, TranslatorBagInterface, LocaleAwareInterface
{
    private string $defaultDomain = 'messages';
    private string $applicationDomain = 'application';
    private string $adminDomain = 'admin';

    /**
     * Translator constructor.
     */
    public function __construct(private readonly BaseTranslator $translator)
    {
    }

    public function getDefaultDomain(): string
    {
        return $this->defaultDomain;
    }

    /**
     * @return $this
     */
    public function setDefaultDomain(string $defaultDomain): Translator
    {
        $this->defaultDomain = $defaultDomain;

        return $this;
    }

    public function trans(string $id, array $parameters = [], string $domain = null, string $locale = null): string
    {
        if ($domain === null) {
            $domain = $this->defaultDomain;
        }

        if ($locale === null) {
            $locale = $this->getLocale();
        }

        if ($domain === $this->applicationDomain) {
            $domain = $this->getMatchingDomain($id, $domain, $locale, $this->applicationDomain);
        } else {
            $domain = $this->getMatchingDomain($id, $domain, $locale, $this->adminDomain);
        }

        return $this->translator->trans($id, $parameters, $domain, $locale);
    }

    public function getLocale(): string
    {
        return $this->translator->getLocale();
    }

    private function getMatchingDomain(string $id, string $domain, string $locale, string $moduleDomain): string
    {
        $catalogue = $this->getCatalogue($locale);
        if ($catalogue->getFallbackCatalogue() !== null && $catalogue->getFallbackCatalogue()->defines($id, $moduleDomain)) {
            return $moduleDomain;
        } elseif ($domain !== $this->defaultDomain) {
            return $domain;
        } else {
            return $this->defaultDomain;
        }
    }

    public function getCatalogue(string $locale = null): MessageCatalogueInterface
    {
        return $this->translator->getCatalogue($locale);
    }

    public function setLocale(string $locale)
    {
        $this->translator->setLocale($locale);
    }

    #[Pure]
 public function getCatalogues(): array
 {
     return $this->translator->getCatalogues();
 }
}
