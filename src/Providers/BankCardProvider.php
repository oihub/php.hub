<?php

namespace PhpHos\Hub\Providers;

use PhpHos\Hub\Client;
use PhpHos\Hub\Services\BankCardService;

/**
 * Class BankCardProvider.
 *
 * @author sean <maoxfjob@163.com>
 */
class BankCardProvider extends Provider
{
    const NAME = 'bankcard';

    public function register(\Pimple\Container $pimple)
    {
        $pimple[static::NAME] = function (Client $hub) {
            return new BankCardService($hub);
        };
    }
}
