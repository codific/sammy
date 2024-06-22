<?php
declare(strict_types=1);

namespace App\Service;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

class ConfigurationService
{

    public function __construct(
        private readonly ParameterBagInterface $parameterBag,
        private readonly TagAwareCacheInterface $redisCache,
    ) {
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->findByKeyRecursive($key, $this->getConfigs()) ?? $default;
    }

    private function findByKeyRecursive(string $key, array $configArray): mixed
    {
        if (array_key_exists($key, $configArray)) {
            return $configArray[$key];
        }
        $parsed = explode('.', $key, 2);
        if (isset($parsed[1]) && array_key_exists($parsed[0], $configArray) && is_array($configArray[$parsed[0]])) {
            return $this->findByKeyRecursive($parsed[1], $configArray[$parsed[0]]);
        }

        return null;
    }

    public function getBool(string $key, bool $default = false): bool
    {
        $result = $this->get($key, (int) $default);
        if (is_string($result)) {
            $result = ($result === 'true' || $result === '1');
        } else {
            $result = (bool) $result;
        }

        return $result;
    }

    public function getInt(string $key, int $default = 0): int
    {
        return (int) $this->get($key, $default);
    }

    public function all(): array
    {
        return $this->getConfigs();
    }

    private function getConfigs(): array
    {
        $key = 'system_config';

        return $this->redisCache->get($key, function (ItemInterface $item) use ($key) {
            $item->expiresAfter(600);
            $item->tag($key);

            return $this->parameterBag->has('sammy') ? $this->parameterBag->get('sammy') : [];
        });
    }
}
