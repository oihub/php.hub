<?php

namespace PhpHos\Hub\Providers;

use PhpHos\Hub\Client;
use PhpHos\Hub\Services\SnowflakeService;

/**
 * Class SnowflakeProvider.
 *
 * @author sean <maoxfjob@163.com>
 */
class SnowflakeProvider extends Provider
{
    const NAME = 'snowflake';

    public function register(\Pimple\Container $pimple)
    {
        $pimple[static::NAME] = function (Client $hub) {
            $config = $hub['config'][static::NAME] ?? [];
            return new SnowflakeService($hub, $config);
        };
    }
}
