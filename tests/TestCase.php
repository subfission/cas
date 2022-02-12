<?php

namespace Subfission\Cas\Tests;

use Subfission\Cas\CasServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app)
    {
        return [
            CasServiceProvider::class,
        ];
    }
}
