<?php
/**
 * This is automatically generated file using the Codific Prototizer.
 *
 * PHP version 8
 *
 * @category PHP
 *
 * @author   CODIFIC <info@codific.eu>
 *
 * @see     http://codific.eu
 */

declare(strict_types=1);

namespace App\Twig;

use App\Entity\Abstraction\AbstractEntity;
use App\Entity\AssessmentStream;
use App\Entity\User;
use App\Enum\Role;
use App\Service\ProjectService;
use App\Utils\Constants;
use App\Utils\DateTimeUtil;
use App\Service\ConfigurationService;
use JetBrains\PhpStorm\NoReturn;
use Symfony\Bridge\Twig\Extension\HttpKernelRuntime;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Yaml\Yaml;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;
use Twig\TwigTest;

class Extensions extends AbstractExtension
{
    public function __construct(
        private readonly RouterInterface $container,
        private readonly TranslatorInterface $translator,
        private readonly ParameterBagInterface $parameterBag,
        private readonly Security $security,
        private readonly DateTimeUtil $dateTimeUtil,
        private readonly ConfigurationService $configurationService,
        private readonly ProjectService $projectService
    ) {
    }

    /**
     * Get the Twig filters.
     *
     * @return array|TwigFilter[]
     */
    public function getFilters(): array
    {
        return [
            new TwigFilter('datePrettyPrint', [$this, 'datePrettyPrint']),
            new TwigFilter('unset', [$this, 'unset']),
            new TwigFilter('bread', [$this, 'bread']),
            new TwigFilter('underscoreEntityName', [$this, 'underscoreEntityName']),
            new TwigFilter('parseDateString', [$this, 'parseDateString']),
            new TwigFilter('dateTimeToUserSettings', [$this, 'dateTimeToUserSettings']),
            new TwigFilter('formatScore', [$this, 'formatScore']),
        ];
    }

    /**
     * @return array|TwigTest[]
     */
    public function getTests(): array
    {
        return [
            new TwigTest('user', [$this, 'user']),
            new TwigTest('admin', [$this, 'admin']),
            new TwigTest('instanceof', [$this, 'isInstance']),
            new TwigTest('existingRoute', [$this, 'existingRoute']),
        ];
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('quit', [$this, 'quit']),
            new TwigFunction('locales', [$this, 'getLocales']),
            new TwigFunction('callStaticMethod', [$this, 'callStaticMethod']),
            new TwigFunction('translations', [$this, 'translations']),
            new TwigFunction('filterFields', [$this, 'filterFields']),
            new TwigFunction('getConfig', [$this, 'getConfig']),
            new TwigFunction('getMaxMaturityLevels', [$this, 'getMaxMaturityLevels']),
            new TwigFunction(
                'generateTooltipPreviewForLinks',
                [
                    $this,
                    'generateTooltipPreviewForLinks',
                ],
                [
                    'is_safe' => ['html'],
                    'needs_environment' => true,
                ]
            ),
            new TwigFunction('getThemeStyleForMetamodel', [$this, 'getThemeStyleForMetamodel']),
            new TwigFunction("getDomainHostName", [$this, 'getDomainHostName']),
            new TwigFunction("getDomain", [$this, 'getDomain']),
            new TwigFunction('getLastImprovementStage', [$this, 'getLastImprovementStage']),
        ];
    }

    public function formatScore(null|float|int $score): string|float
    {
        if ($score === null) {
            return number_format(0, 2);
        } elseif ((int)$score === -1) {
            return "Not applicable";
        } else {
            return number_format($score, 2);
        }
    }

    public function translations(string $locale): string
    {
        $path = $this->parameterBag->get('kernel.project_dir').'/translations/';
        $translations = Yaml::parseFile("{$path}messages+intl-icu.{$locale}.yaml");

        return json_encode($translations);
    }

    public function getMaxMaturityLevels(int $metamodelId): int
    {
        return Constants::getMaxMaturityLevels($metamodelId);
    }

    /**
     * @return mixed
     */
    public function callStaticMethod(string $className, string $propertyName)
    {
        $class = "\\App\Entity\\$className";

        /* @phpstan-ignore-next-line */
        return $class::{"getAll$propertyName"}();
    }

    public function filterFields(string $className): string
    {
        $fields = [];
        $class = '\\App\Entity\\'.ucfirst($className);
        $classInstance = new $class();
        /** @var AbstractEntity $classInstance */
        $filterFields = $classInstance->getFilterFields();
        foreach ($filterFields as $field) {
            $columns = explode('.', $field);
            $entityName = trim(strtolower(preg_replace('/[A-Z]([A-Z](?![a-z]))*/', '_$0', $className)), '_');
            $columnName = trim(strtolower(preg_replace('/[A-Z]([A-Z](?![a-z]))*/', '_$0', end($columns))), '_');
            $fields[] = $this->translator->trans("admin.$entityName.$columnName");
        }

        return join(', ', $fields);
    }

    /**
     * Stop php execution.
     */
    #[NoReturn]
    public function quit(?string $message = '')
    {
        exit($message);
    }

    /**
     * This can be refactored into different checks for different user roles, for now it only differentiates between admin and non admin.
     *
     * @return bool
     */
    public function user($entity)
    {
        return $entity instanceof User && !$this->security->isGranted(Role::ADMINISTRATOR->string());
    }

    /**
     * @return bool
     */
    public function admin($entity)
    {
        return $entity instanceof User && $this->security->isGranted(Role::ADMINISTRATOR->string());
    }

    /**
     * @return bool
     */
    public function isInstance($entity, string $className)
    {
        $fullClassName = "\\App\Entity\\$className";

        return $entity instanceof $fullClassName;
    }

    /**
     * Get underscore name from full controller name.
     */
    public function underscoreEntityName(string $controllerName): string
    {
        $temp = explode('\\', $controllerName);

        return trim(
            strtolower(preg_replace('/[A-Z]([A-Z](?![a-z]))*/', '_$0', str_ireplace('Controller', '', end($temp)))),
            '_'
        );
    }

    /**
     * @return bool
     */
    public function existingRoute(string $name)
    {
        return $this->container->getRouteCollection()->get($name) !== null;
    }

    /**
     * Return a pretty print version for date.
     */
    public function datePrettyPrint(?\DateTime $dateTime, string $format = 'd-m-Y', ?string $defaultDate = null): string
    {
        if ($defaultDate === null) {
            $defaultDate = $this->translator->trans('application.general.no_date', [], 'application');
        }

        if ($dateTime !== null) {
            return $dateTime->format($format);
        }

        return $defaultDate;
    }

    /**
     * @throws \Exception
     */
    public function parseDateString(string $dateString): \DateTime
    {
        return new \DateTime($dateString);
    }

    /**
     * Unset an element from an array.
     *
     * @return array
     */
    public function unset(array $array = [], ?string $key = null)
    {
        if (isset($array[$key])) {
            unset($array[$key]);
        }

        return $array;
    }

    public function dateTimeToUserSettings(?\DateTime $dateTime, string $timeFormat = 'H:i:s', int $prependSchema = DateTimeUtil::PREPEND_SCHEMA_TIME_THEN_DATE): string
    {
        if ($dateTime === null) {
            return '';
        }
        $user = $this->security->getUser();

        return $this->dateTimeUtil->convertDateTimeToUserSettings($dateTime, $user, $timeFormat, $prependSchema);
    }

    public function getConfig(string $key, $default = null): bool|float|int|string|null
    {
        return $this->configurationService->get($key, $default);
    }

    public function generateTooltipPreviewForLinks(Environment $env, ?string $htmlContent): ?string
    {
        if ($htmlContent === null || strlen($htmlContent) === 0) {
            return null;
        }
        $crawler = new Crawler($htmlContent);
        $endpointFirstPart = preg_replace(
            '/\/\{\w*\}/',
            '',
            $this->container->getRouteCollection()->get('app_documentation_show')->getPath()
        );

        $linksCrawler = $crawler->filterXPath("//a[starts-with(@href,'{$endpointFirstPart}')]");
        /** @var \DOMElement $linkNode */
        foreach ($linksCrawler as $linkNode) {
            $this->generateHTMLTooltipForLink($env, $linkNode);
        }

        return $crawler->html();
    }

    private function generateHTMLTooltipForLink(Environment $env, \DOMElement $link)
    {
        try {
            $link->setAttribute('data-toggle', 'tooltip');
            $link->setAttribute('data-html', 'true');
            $link->setAttribute('data-placement', 'left');
            $link->setAttribute('target', '_blank');

            $href = $link->getAttribute('href');

            $runtime = $env->getRuntime(HttpKernelRuntime::class);

            $link->setAttribute(
                'data-original-title',
                $runtime->renderFragment(
                    preg_replace(
                        '/show/',
                        'preview',
                        $href,
                        1
                    )
                )
            );
        } catch (\Throwable) {
            // do nothing
        }
    }

    public function getThemeStyleForMetamodel(): string
    {
        $user = $this->security->getUser();
        if ($user === null) {
            return 'bg-slick-carbon';
        }

        $metamodelId = $this->projectService->getCurrentProject()?->getMetamodel()?->getId();

        return match ($metamodelId) {
            default => 'bg-slick-carbon'
        };
    }

    public function getDomainHostName($url)
    {
        $parsedUrl = parse_url($url);
        $host = $parsedUrl['host'] ?? '';
        $urlParts = explode('.', $host);

        return $urlParts[0];
    }

    public function getDomain($url)
    {
        $parsedUrl = parse_url($url);
        $scheme = $parsedUrl['scheme'] ?? '';
        $host = $parsedUrl['host'] ?? '';
        $path = $parsedUrl['path'] ?? '';
        $firstEndpoint = "/".(explode("/", $path)[1] ?? "");

        return $scheme."://".$host.$firstEndpoint;
    }

    public function getLastImprovementStage(AssessmentStream $assessmentStream): ?\App\Entity\Improvement
    {
        return $assessmentStream->getLastImprovementStage();
    }
}
