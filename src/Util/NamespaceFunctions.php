<?php
declare(strict_types=1);

namespace App\Util;

use Symfony\Component\HttpFoundation\Request;

class NamespaceFunctions
{
    public static function getUrlNamespace(Request $request): string
    {
        return str_starts_with($request->attributes->get('_route'), 'admin') ? 'admin' : 'app';
    }

    public static function getTemplateNamespace(Request $request): string
    {
        return str_starts_with($request->attributes->get('_route'), 'admin') ? 'admin' : 'application';
    }
}
