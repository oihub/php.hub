<?php

namespace PhpHos\Hub\Providers;

use PhpHos\Hub\Client;
use PhpHos\Hub\Services\ExpressService;

/**
 * Class ExpressProvider.
 *
 * @author sean <maoxfjob@163.com>
 */
class ExpressProvider extends Provider
{
    const NAME = 'express';

    public function register(\Pimple\Container $pimple)
    {
        $pimple[static::NAME] = function (Client $hub) {
            $config = $hub['config'][static::NAME] ?? [];
            return new ExpressService($hub, $config);
        };
    }
}
