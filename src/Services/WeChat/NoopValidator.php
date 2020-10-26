<?php

namespace PhpHos\Hub\Services\WeChat;

use WechatPay\GuzzleMiddleware\Validator;
use Psr\Http\Message\ResponseInterface;

/**
 * Class NoopValidator.
 *
 * @author sean <maoxfjob@163.com>
 */
class NoopValidator implements Validator
{
    public function validate(ResponseInterface $response)
    {
        return true;
    }
}
