<?php

namespace Subfission\Cas;

use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;

class LogFactory
{
    public function make(): ?LoggerInterface
    {
        $logType = config('cas.cas_log');

        if ($logType === 'laravel') {
            return \Log::getLogger();
        }

        if (!empty($logType)) {
            $log = new Logger('phpCAS');
            $log->pushHandler(new StreamHandler($logType));
            return $log;
        }

        return null;
    }
}
