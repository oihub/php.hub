<?php

namespace PhpHos\Hub\Providers;

use PhpHos\Hub\Client;
use PhpHos\Hub\Services\SequenceService;

/**
 * Class SequenceProvider.
 *
 * @author sean <maoxfjob@163.com>
 */
class SequenceProvider extends Provider
{
    const NAME = 'sequence';

    public function register(\Pimple\Container $pimple)
    {
        $pimple[static::NAME] = function (Client $hub) {
            $config = $hub['config'][static::NAME] ?? [];
            return new SequenceService($hub, $config);
        };
    }
}
