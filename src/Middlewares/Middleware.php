<?php

namespace PhpHos\Hub\Middlewares;

use PhpHos\Hub\Client;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Class Middleware.
 * 
 * @author sean <maoxfjob@163.com>
 */
abstract class Middleware
{
    protected $hub;

    protected $config = [];

    public function __construct(Client $hub, array $config = [])
    {
        $this->hub = $hub;
        $this->config = $config;

        $this->init();
    }

    public static function make(Client $hub, array $config = [])
    {
        return new static($hub, $config);
    }

    public function build(): callable
    {
        return function (callable $handler) {
            return function ($request, $options) use ($handler) {
                $this->request($request, $options);
                return $handler($request, $options)
                    ->then(function ($response) use ($request) {
                        $this->response($request, $response);
                        return $response;
                    });
            };
        };
    }

    protected function init(): void
    {
    }

    protected function request(
        RequestInterface &$request,
        array &$options
    ): void {
    }

    protected function response(
        RequestInterface &$request,
        ResponseInterface &$response
    ): void {
    }
}
