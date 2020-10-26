<?php

namespace PhpHos\Hub\Providers;

use PhpHos\Hub\Client;
use PhpHos\Hub\Services\SensitiveService;

/**
 * Class SensitiveProvider.
 *
 * @author sean <maoxfjob@163.com>
 */
class SensitiveProvider extends Provider
{
    const NAME = 'sensitive';

    public function register(\Pimple\Container $pimple)
    {
        $pimple[static::NAME] = function (Client $hub) {
            return new SensitiveService($hub);
        };
    }
}
