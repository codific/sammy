<?php

declare(strict_types=1);

namespace App\Security\Token;

use Symfony\Component\Security\Http\Authenticator\Token\PostAuthenticationToken;

class SsoToken extends PostAuthenticationToken
{
}
