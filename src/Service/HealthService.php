<?php

declare(strict_types=1);

namespace App\Service;

use Doctrine\Migrations\DependencyFactory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class HealthService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly DependencyFactory $dependencyFactory,
        private readonly ParameterBagInterface $parameterBag,
    ) {
    }

    // Everything below is final
    private static array $required = [
        'app',
        'env',
        'database',
    ];
    private static array $optional = [];
    private static array $extraData = [];

    final public static function getRequiredModules(): array
    {
        return array_merge(self::$required);
    }

    final public static function getOptionalModules(): array
    {
        return array_merge(self::$optional);
    }

    final public static function getExtraData(): array
    {
        return array_merge(self::$extraData);
    }

    public function __call(string $method, array $arguments)
    {
        if (method_exists($this, $method)) {
            return call_user_func_array($this->$method, $arguments); // @phpstan-ignore-line
        }

        $thisClass = get_class($this);
        throw new \BadMethodCallException("Method \"$method\" not found. Checked in $thisClass");
    }

    // Dummy method
    final public function checkApp(): void
    {
    }

    final public function checkDatabase(): void
    {
        $con = $this->entityManager->getConnection();
        $con->executeQuery('select 1');

        // Check if there is a new migration which is not executed
        $statusCalculator = $this->dependencyFactory->getMigrationStatusCalculator();
        $newMigrations = $statusCalculator->getNewMigrations();
        if (count($newMigrations) > 0) {
            throw new \Exception('Failed migrations : '.json_encode($newMigrations));
        }
    }

    final public function checkEnv(): void
    {
        // Quick and easy but will only catch the first one that fails. There may be a better way
        $this->parameterBag->all();
    }
}
