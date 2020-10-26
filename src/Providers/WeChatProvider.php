<?php

namespace PhpHos\Hub\Providers;

use PhpHos\Hub\Client;
use PhpHos\Hub\Services\WeChatService;

/**
 * Class WeChatProvider.
 *
 * @author sean <maoxfjob@163.com>
 */
class WeChatProvider extends Provider
{
    const NAME = 'wechat';

    public function register(\Pimple\Container $pimple)
    {
        $pimple[static::NAME] = function (Client $hub) {
            $config = $hub['config'][static::NAME] ?? [];
            return new WeChatService($hub, $config);
        };
    }
}
