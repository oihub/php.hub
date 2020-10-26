<?php

namespace PhpHos\Hub\Middlewares;

use GuzzleHttp\Middleware as HttpMiddleware;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Class RetryMiddleware.
 * 
 * $config = [
 *      'max' => 1,
 *      'delay' => 500,
 *      'handler' => function ($response) {
 *          return false;
 *      },
 * ];
 * 
 * @author sean <maoxfjob@163.com>
 */
class RetryMiddleware extends Middleware
{
    protected function init(): void
    {
        $this->config = array_merge([
            'max' => 1,
            'delay' => 500,
            'handler' => function ($response) {
                return false;
            },
        ], $this->config);
    }

    public function build(): callable
    {
        return HttpMiddleware::retry(
            function (
                $retries,
                RequestInterface $request,
                ResponseInterface $response = null,
                RequestException $exception = null
            ) {
                return $this->decider(
                    $retries,
                    $request,
                    $response,
                    $exception
                );
            },
            function () {
                return $this->delay();
            }
        );
    }

    protected function decider(
        $retries,
        RequestInterface $request,
        ResponseInterface $response = null,
        RequestException $exception = null
    ): bool {
        // 请求超过最大重试次数，不再重试.
        if ($retries >= $this->config['max']) {
            return false;
        }

        // 请求失败，继续重试.
        if ($exception instanceof ConnectException) {
            return true;
        }

        // 请求有响应，根据业务处理.
        if ($response) {
            return call_user_func(
                $this->config['handler'],
                $retries,
                $request,
                $response,
                $exception
            );
        }

        return false;
    }

    protected function delay(): int
    {
        return $this->config['delay'];
    }
}
