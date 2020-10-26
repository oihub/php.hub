<?php

namespace PhpHos\Hub\Providers;

use GuzzleHttp\Client as HttpClient;
use PhpHos\Hub\Client;

/**
 * Class HttpProvider.
 *
 * @author sean <maoxfjob@163.com>
 */
class HttpProvider extends Provider
{
    const NAME = 'http';

    public function register(\Pimple\Container $pimple)
    {
        $pimple[static::NAME] = function (Client $hub) {
            $config = $hub['config'][static::NAME] ?? [];
            return new HttpClient($config);
        };
    }
}
