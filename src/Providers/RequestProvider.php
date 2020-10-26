<?php

namespace PhpHos\Hub\Providers;

use PhpHos\Hub\Client;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class RequestProvider.
 *
 * @author sean <maoxfjob@163.com>
 */
class RequestProvider extends Provider
{
    const NAME = 'request';

    public function register(\Pimple\Container $pimple)
    {
        $pimple[static::NAME] = function (Client $hub) {
            return Request::createFromGlobals();
        };
    }
}
