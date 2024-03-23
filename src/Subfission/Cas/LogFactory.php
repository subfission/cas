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

        // Use the active Laravel logger given by the facade
        if (strtolower($logType) === 'laravel') {
            return \Log::getLogger();
        }

        // Assume the value is a path for a log file
        // We are not responsible for file system errors at this point
        if (!empty($logType)) {
            $log = new Logger('phpCAS');
            $log->pushHandler(new StreamHandler($logType));
            return $log;
        }

        // Disable logging
        return null;
    }
}
