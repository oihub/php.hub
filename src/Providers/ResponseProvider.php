<?php

namespace PhpHos\Hub\Providers;

use PhpHos\Hub\Client;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class ResponseProvider.
 *
 * @author sean <maoxfjob@163.com>
 */
class ResponseProvider extends Provider
{
    const NAME = 'response';

    public function register(\Pimple\Container $pimple)
    {
        $pimple[static::NAME] = function (Client $hub) {
            return new Response();
        };
    }
}
