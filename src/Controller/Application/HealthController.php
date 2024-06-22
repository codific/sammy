<?php

declare(strict_types=1);

namespace App\Controller\Application;

use App\Helper\Status;
use App\Service\HealthService;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

readonly class HealthController
{
    public function __construct(
        private HealthService $healthService,
        private ParameterBagInterface $parameterBag,
        private LoggerInterface $logger,
    ) {
    }

    #[Route('/api/health', name: 'health')]
    public function health(): Response
    {
        $core = $this->requiredModules();
        $build = $this->getBuild();

        $ok = $this->modulesAreOk($core);

        $message = [];
        if ($build !== null) {
            $message['build'] = $build;
        }
        if ($ok === false) {
            $this->logger->critical($this->info());
        }

        return new JsonResponse($message, $ok ? Response::HTTP_OK : Response::HTTP_SERVICE_UNAVAILABLE);
    }

    #[Route('/api/info', name: 'info')]
    public function info(): Response
    {
        $modules = $this->getModules();

        $extraData = $this->getExtraData();

        $build = $this->getBuild();

        $ok = $this->modulesAreOk($modules['core']);
        $optional = $this->modulesAreOk($modules['optional']);
        [$health, $status] = $this->getHealth($ok, $optional);

        $finalData = $this->getFinalData($health, $build, $modules, $extraData);

        return new JsonResponse($finalData, $status);
    }

    private function getFinalData(string $health, ?string $build, array $modules, array $extraData): array
    {
        $finalData = [];
        $finalData['health'] = $health;

        if ($build !== null) {
            $finalData['build'] = $build;
        }
        $finalData['modules'] = $modules;
        if ($extraData !== []) {
            $finalData['extra data'] = $extraData;
        }

        return $finalData;
    }

    /**
     * @return array{ core : Status[], optional : Status[]}
     */
    private function getModules(): array
    {
        $modules = [];
        $modules['core'] = $this->requiredModules();
        $modules['optional'] = $this->optionalModules();

        return $modules;
    }

    private function requiredModules(): array
    {
        $core = [];
        // Required modules
        foreach (HealthService::getRequiredModules() as $module) {
            $core[$module] = $this->getStatus($module, 3);
        }

        return $core;
    }

    private function optionalModules(): array
    {
        $optional = [];
        // Optional modules
        foreach (HealthService::getOptionalModules() as $module) {
            $optional[$module] = $this->getStatus($module, 2);
        }

        return $optional;
    }

    private function getStatus(string $module, int $loglevel = 3): Status
    {
        try {
            $this->healthService->{'check' . $module}(); // @phpstan-ignore-line

            return new Status(true);
        } catch (\Throwable $exception) {
            $this->logWithLevel($exception, $loglevel);

            return new Status(false, $exception->getMessage());
        }
    }

    private function getExtraData(): array
    {
        $extraData = [];
        // Extra info
        foreach (HealthService::getExtraData() as $extraDatum) {
            $extraData[$extraDatum] = $this->getExtra($extraDatum);
        }

        return $extraData;
    }

    private function getExtra(string $extraName)
    {
        try {
            return $this->healthService->{'extra' . $extraName}(); // @phpstan-ignore-line
        } catch (\Throwable $exception) {
            $this->logWithLevel($exception, 1);

            return $exception->getMessage();
        }
    }

    private function getBuild(): ?string
    {
        $buildFile = "{$this->parameterBag->get('kernel.project_dir')}/build.txt";
        if (file_exists($buildFile) && is_file($buildFile)) {
            return trim(file_get_contents($buildFile));
        }

        return null;
    }

    private function getHealth(bool $required, bool $optional): array
    {
        return match ([$required, $optional]) {
            [true, true] => ['Healthy', Response::HTTP_OK],
            [true, false] => ['Operational', 577],
            [false, true], [false, false] => ['Critical', Response::HTTP_SERVICE_UNAVAILABLE],
        };
    }

    private function logWithLevel(\Throwable $throwable, $level = 3): void
    {
        match ($level) {
            0 => $this->logger->debug($throwable->getMessage(), $throwable->getTrace()),
            1 => $this->logger->warning($throwable->getMessage(), $throwable->getTrace()),
            2 => $this->logger->error($throwable->getMessage(), $throwable->getTrace()),
            default => $this->logger->critical($throwable->getMessage(), $throwable->getTrace()),
        };
    }

    private function modulesAreOk($modules): bool
    {
        return array_reduce($modules, function ($flag, Status $module) {
            return $flag && $module->ok;
        }, true);
    }
}
