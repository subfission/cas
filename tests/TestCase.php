<?php

namespace Subfission\Cas\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Subfission\Cas\CasServiceProvider;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app)
    {
        return [
            CasServiceProvider::class,
        ];
    }
}
