<?php

declare(strict_types=1);

namespace App\Handler;

class Redis extends \Redis
{
    public function __construct($host, $port, ?string $password, string $prefix = '')
    {
        parent::__construct();
        $this->connect($host, (int) $port);
        $this->setOption(Redis::OPT_SERIALIZER, Redis::SERIALIZER_PHP);
        $this->setOption(Redis::OPT_PREFIX, $prefix);
        if ($password !== null) {
            $this->auth($password);
        }
    }
}
