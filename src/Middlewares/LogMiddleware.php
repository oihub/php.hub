<?php

namespace PhpHos\Hub\Middlewares;

use GuzzleHttp\Middleware as HttpMiddleware;
use GuzzleHttp\MessageFormatter;
use Monolog\Logger;
use Monolog\Formatter\LineFormatter;

/**
 * Class LogMiddleware.
 * 
 * $config = [
 *      [
 *         'driver' => 'StreamHandler',
 *         'params' => [
 *             'path' => sys_get_temp_dir() . '/phphos/hub/http.log';
 *             'level' => Logger::DEBUG,
 *         ],
 *         'formatter' => [
 *             'format' => '[%datetime%] [%message%]',
 *             'dateFormat' => 'Y-m-d H:i:s',
 *         ],
 *      ],
 * ];
 * 
 * @author sean <maoxfjob@163.com>
 */
class LogMiddleware extends Middleware
{
    protected function init(): void
    {
        $path = sys_get_temp_dir() . '/phphos/hub/http.log';
        $this->config or $this->config = [[]];

        foreach ($this->config as &$item) {
            $item = array_merge([
                'driver' => 'StreamHandler',
                'params' => [
                    'path' => $path,
                    'level' => Logger::DEBUG,
                ],
                'formatter' => [
                    'format' => '[%datetime%] [%channel%] [%level_name%] [%message%] [%context%] [%extra%]' . "\n",
                    'dateFormat' => 'Y-m-d H:i:s',
                ],
            ], $item);
        }
    }

    public function build(): callable
    {
        $handlers = $this->handlers();
        $logger = new Logger($this->hub::NAME, $handlers);
        $formatter = new MessageFormatter(MessageFormatter::DEBUG);
        return HttpMiddleware::log($logger, $formatter);
    }

    protected function handlers(): array
    {
        $handlers = [];

        foreach ($this->config as $driver) {

            $class = '\\Monolog\Handler\\' . $driver['driver'];
            $params = array_values($driver['formatter']);
            $formatter = new LineFormatter(...$params);

            $params = array_values($driver['params']);
            $handler = new $class(...$params);
            $handler->setFormatter($formatter);

            $handlers[] = $handler;
        }

        return $handlers;
    }
}
