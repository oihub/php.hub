<?php

namespace PhpHos\Hub\Providers;

use PhpHos\Hub\Client;
use PhpHos\Hub\Services\WeChatPayService;

/**
 * Class WeChatPayProvider.
 *
 * @author sean <maoxfjob@163.com>
 */
class WeChatPayProvider extends Provider
{
    const NAME = 'wechatpay';

    public function register(\Pimple\Container $pimple)
    {
        $pimple[static::NAME] = function (Client $hub) {
            $config = $hub['config'][static::NAME] ?? [];
            return new WeChatPayService($hub, $config);
        };
    }
}
