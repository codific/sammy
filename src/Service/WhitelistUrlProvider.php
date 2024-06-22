<?php

declare(strict_types=1);


namespace App\Service;

class WhitelistUrlProvider
{
    private array $whitelistedUrls;

    public function __construct()
    {
        $this->whitelistedUrls = [];
    }

    public function getWhitelistedUrls(): array
    {
        return $this->whitelistedUrls;
    }

    public function setWhitelistedUrls($whitelistedUrls): void
    {
        $this->whitelistedUrls = $whitelistedUrls;
    }
}
