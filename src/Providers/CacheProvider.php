<?php

namespace PhpHos\Hub\Providers;

use PhpHos\Hub\Client;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\Psr16Cache;

/**
 * Class CacheProvider.
 *
 * @author sean <maoxfjob@163.com>
 */
class CacheProvider extends Provider
{
    const NAME = 'cache';

    public function register(\Pimple\Container $pimple)
    {
        $pimple[static::NAME] = function (Client $hub) {
            $adapter = new FilesystemAdapter($hub::NAME, 1500);
            return new Psr16Cache($adapter);
        };
    }
}
