<?php

declare(strict_types=1);


namespace App\Utils;


use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Matcher\UrlMatcherInterface;

class RouteValidator
{


    public static function validateRoute(
        Request $request,
        UrlMatcherInterface $router,
        ?string $url = "",
        string $method = Request::METHOD_GET,
        array $whitelist = []
    ): bool {
        try {
            $safeScheme = function () use ($url, $request) {
                return parse_url($url, PHP_URL_SCHEME) === $request->getScheme();
            };

            $internalUrl = function () use ($url, $request) {
                return parse_url($url, PHP_URL_HOST) === $request->getHost();
            };

            $whitelistedUrl = function () use ($url, $whitelist) {
                return in_array(parse_url($url, PHP_URL_HOST), $whitelist, true);
            };

            $safeUrl = fn() => ($safeScheme() && ($internalUrl() || $whitelistedUrl()));

            if ($url === null || !$safeUrl()) {
                return false;
            }

            if ($internalUrl()) {
                $hostname = $request->getSchemeAndHttpHost();
                $temp = substr($url, strpos($url, $hostname) + strlen($hostname));
                $temp = parse_url($temp, PHP_URL_PATH);
                $router->getContext()->setMethod($method);
                $router->match($temp);
            }

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
