<?php

declare(strict_types=1);

namespace App\Processors;

use Monolog\Attribute\AsMonologProcessor;
use Monolog\JsonSerializableDateTimeImmutable;
use Monolog\Level;
use Monolog\LogRecord;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

#[AsMonologProcessor]
class MainLogProcessor
{
    public function __construct()
    {
    }

    public function __invoke(LogRecord $record): LogRecord
    {
        if (str_contains($record['message'], 'Uncaught PHP Exception Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException') &&
            $record['channel'] === 'request' &&
            $record['level'] === 400 &&
            $record['context']['exception'] instanceof AccessDeniedHttpException &&
            $record['context']['exception']->getMessage() === 'Access Denied by #[IsGranted("ROLE_MANAGER")] on controller') {
            // if you want to append extra data to the log, before it's written ↓
            // $record->extra['custom_log'] = "<extra appended data>";

            // returns an empty record, which will be written, instead of the 'Uncaught PHP Exception AccessDeniedHttpException'
            return new LogRecord(
                new JsonSerializableDateTimeImmutable(true),
                'skip',
                Level::Info,
                '',
                [],
                []
            );
        }

        // passes the default log without modifications
        return $record;
    }
}
