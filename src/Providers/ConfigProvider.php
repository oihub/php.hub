<?php

namespace PhpHos\Hub\Providers;

use PhpHos\Hub\Client;
use PhpHos\Hub\Collection;

/**
 * Class ConfigProvider.
 *
 * @author sean <maoxfjob@163.com>
 */
class ConfigProvider extends Provider
{
    const NAME = 'config';

    public function register(\Pimple\Container $pimple)
    {
        $pimple[static::NAME] = function (Client $hub) {
            return new Collection($hub->getConfig());
        };
    }
}
